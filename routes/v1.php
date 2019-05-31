<?php 

use Exception\NotFoundException;
use Exception\ForbiddenException;
use Exception\PreconditionFailedException;
use Exception\PreconditionRequiredException;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\Collection;
use League\Fractal\Serializer\DataArraySerializer;
use Tuupola\Base62;
use App\Ecmalog;
use App\User;
use App\UserType;
use App\Config;
use App\Email;
use App\Section;
use App\Post;
use App\Todo;

$app->group('/v1', function() {

    $this->post("/ecmalog", function($request, $response, $arguments){
        if(getenv('ECMALOG_LEVEL')){
            $body = $request->getParsedBody();
            $data = $this->spot->mapper("App\Ecmalog")->save(new App\Ecmalog($body));

            return $response->withStatus(200)
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode([$data]));
        }
    });

    $this->put("/testp", function ($request, $response, $arguments) {
            $body = $request->getParsedBody();
            extract($body);
            return $response->withStatus(200)
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($body));                
    });

    $this->post("/contact", function ($request, $response, $arguments) {

        $body = $request->getParsedBody();

        $user = (object) [
            'first_name' => "PuntoWeberPlast [Auto]",
            'last_name' => "(no responder)",
            'email' => getenv('MAIL_CONTACT')
        ];

        $data["status"] = "error";
        $user2 = \register_if_not_exists($body['email']);
        $sent = \send_email_template("EMAIL_CONTACT",$user,$body);

        $this->spot->mapper("App\Contact")->save(new App\Contact($body));

        if($sent){
            $data["status"] = "success";
        }
        
        return $response->withStatus(200)
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($data));
    });

    $this->post('/navitems', function ($request, $response, $args) {

        $mapper = $this->spot->mapper("App\Section")
            ->where(['enabled' => 1])
            ->where(['is_navitem' => 1]);
            //->orWhere(['is_footer' => 1]);
            $data = [];
        $navitems = [];
        foreach($mapper as $item){
            $navitems[] = (object) [
                'parent' => ($item->parent ? $item->parent->title : null),
                'title' => $item->title,
                'slug' => $item->slug,
                'icon' => $item->icon,
                'is_navitem' => $item->is_navitem,
                'is_footer' => $item->is_footer
            ];
        }
        $data['navbar'] = getenv('APP_NAVBAR');
        $data['footer'] = getenv('APP_FOOTER');
        $data['navitems'] = $navitems;

        return $response->withStatus(200)
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($data));
    });

    $this->post('/sections[/{slug:.*}]', function ($request, $response, $args) {
        $slug = str_replace('.','/',$args['slug']);
        $data = null;
        $mapper = $this->spot->mapper("App\Section")
            ->where(['slug' => '/'.$slug])
            ->where(['enabled' => 1])
            ->first();

        if($mapper === false){
            throw new ForbiddenException("No resource was found.", 404);
        }

        /* Serialize the response data. */
        $fractal = new Manager();
        $fractal->setSerializer(new DataArraySerializer);
        $resource = new Item($mapper, new Section);
        $data = $fractal->createData($resource)->toArray()['data'];

        return $response->withStatus(200)
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($data));
    });

    $this->group('/account', function() {

        $this->get("/todos", function ($request, $response, $arguments) {

            if (false === $this->token->decoded->uid) {
                throw new ForbiddenException("Token expired or invalid.", 403);
            }


            $mapper = $this->spot->mapper("App\Todo")
                ->where(['user_id' => $this->token->decoded->uid ])
                ->order(['id' => "DESC"]);

            /* Serialize the response data. */
            $fractal = new Manager();
            $fractal->setSerializer(new DataArraySerializer);
            $resource = new Collection($mapper, new Todo);
            $data = $fractal->createData($resource)->toArray()['data'];

            return $response->withStatus(200)
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($data));

        });

        $this->get("/todos/{id}", function ($request, $response, $arguments) {

            if (false === $this->token->decoded->uid) {
                throw new ForbiddenException("Token expired or invalid.", 403);
            }

            $mapper = $this->spot->mapper("App\Todo")
                ->where(['user_id' => $this->token->decoded->uid ])
                ->where(['id' => $request->getAttribute('id') ])
                ->first();

            /* Serialize the response data. */
            $fractal = new Manager();
            $fractal->setSerializer(new DataArraySerializer);
            $resource = new Item($mapper, new Todo);
            $data = $fractal->createData($resource)->toArray()['data'];

            return $response->withStatus(200)
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($data));            
        });

        $this->put("/todos[/{id:.*}]", function ($request, $response, $arguments) {

            if (false === $this->token->decoded->uid) {
                throw new ForbiddenException("Token expired or invalid.", 403);
            }

            $body = $request->getParsedBody();
            $body['user_id'] = $this->token->decoded->uid;

            if(empty($request->getAttribute('id'))){
                $mapper = new App\Todo();
            } else {
                $mapper = $this->spot->mapper("App\Todo")
                    ->where(['user_id' => $this->token->decoded->uid ])
                    ->where(['id' => (int) $request->getAttribute('id') ])
                    ->first();
            }

            $mapper->data($body);
            $this->spot->mapper("App\Todo")->save($mapper);

            /* Serialize the response data. */
            $fractal = new Manager();
            $fractal->setSerializer(new DataArraySerializer);
            $resource = new Item($mapper, new Todo);
            $data = $fractal->createData($resource)->toArray()['data'];

            return $response->withStatus(200)
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($data));            
        });

        $this->post("/password", function ($request, $response, $arguments) {

            if (false === $this->token->decoded->uid) {
                throw new ForbiddenException("Token expired or invalid.", 403);
            }

            $body = $request->getParsedBody();

            if(empty($body)){
                throw new ForbiddenException("No parameters recieved.", 403);
            }

            $data = [];
            $user = $this->spot->mapper("App\User")->first([
                'id' => $this->token->decoded->uid,
                'enabled' => 1,
                'password' => sha1($body['password'].getenv('APP_HASH_SALT'))
            ]);
           
            if(!$user) { 
                $data['status'] = "error";
                $data['message'] = "Password is invalid. Changes were not saved.";
                $data['messageType'] = "is-danger";
            } else {
                
                $hash = sha1($body['new_password'].getenv('APP_HASH_SALT'));
                
                $user->data(['password' => $hash]);
                $this->spot->mapper("App\User")->save($user);

                $data['status'] = "success";
                $data['message'] = "Password was successfully updated.";
                $data['messageType'] = "is-success";
            }

            return $response->withStatus(200)
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($data)); 

        });

        $this->post("/update", function ($request, $response, $arguments) {

            $body = $request->getParsedBody();
            $data = [];

            if (false === $this->token->decoded->uid) {
                throw new ForbiddenException("Token expired or invalid.", 403);
            }

            if(empty($body)){
                throw new ForbiddenException("No parameters recieved.", 403);
            }

            $user = $this->spot->mapper("App\User")->first([
                "id" => $this->token->decoded->uid
            ]);   

            if(!$user) { 
                $data['status'] = "error";
            } else {
                $user->data($body);
                $this->spot->mapper("App\User")->save($user);

                $fractal = new Manager();
                $fractal->setSerializer(new DataArraySerializer);
                $resource = new Item($user, new User);
                $data = $fractal->createData($resource)->toArray()['data'];
            }

            return $response->withStatus(200)
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($data));                 
        });

        $this->post("/profile-picture", function ($request, $response, $arguments) {

            if (false === $this->token->decoded->uid) {
                throw new ForbiddenException("Token expired or invalid.", 403);
            }

            $mapper = $this->spot->mapper("App\User")->first([
                "id" => $this->token->decoded->uid
            ]);

            if( ! $mapper){
                throw new NotFoundException("User not found. (1)", 404);
            }

            $body = $request->getParsedBody();
            
            $data = \process_uploads($body,['user_id' => $mapper->id],getenv('APP_IMAGE_USER'),'200x200');

            $mapper->data(['picture' => $data['url']]);

            $this->spot->mapper("App\User")->save($mapper);

            return $response->withStatus(200)
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($data));
        }); 
    });
});

$app->post("/upload/simple", function ($request, $response, $arguments) {
    $file = $_FILES['file'];
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $key = uniqid() . '.' . $ext;
    $dest = getenv('UPLOADS_PATH') . '/' . $key;
    $url = "";

    if(copy($file['tmp_name'],$dest)){
        $url = getenv('UPLOADS_URL') . '/' . $key;
    }

    return $response->withStatus(200)
        ->withHeader("Content-Type", "application/json")
        ->write(json_encode($url));
});

$app->get("/m/{code}", function ($request, $response, $arguments) {

    $mapper = $this->spot->mapper("App\Email")->first([
        "code" => $request->getAttribute('code')
    ]);

    if( ! $mapper){
        throw new NotFoundException("No se encontró el email", 404);        
    }

    header('Content-Type: text/html; charset=utf-8');
    print $mapper->content;
    exit;
});


$app->get('/{slug:.*}', function ($request, $response, $args) {
    return $this->view->render($response, 'index.html');
});  
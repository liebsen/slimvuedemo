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
use App\User;
use App\Email;

$app->group('/v1', function() {

    $this->group('/auth', function() {

        $this->post("/token", function ($request, $response, $arguments) {

            if ($this->token->decoded->uid) {
                
                $user = $this->spot->mapper("App\User")->first([
                    "id" => $this->token->decoded->uid,
                    'enabled' => 1
                ]);

                $data = [];

                if ($user) {

                    $user->data(['last_activity' => date('Y-m-d H:i')]);
                    $this->spot->mapper("App\User")->save($user);
                    
                    $fractal = new Manager();
                    $fractal->setSerializer(new DataArraySerializer);
                    $resource = new Item($user, new User);
                    $data = $fractal->createData($resource)->toArray()['data'];
                }
            }

            return $response->withStatus(200)
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($data));                
        });

        $this->post("/signin", function ($request, $response, $arguments) {
            $body = $request->getParsedBody();

            $user = $this->spot->mapper("App\User")->first([
                'email' => $body['email'],
                'enabled' => 1,
                'password_hash' => sha1($body['password'].getenv('APP_HASH_SALT'))
            ]);

            if($user){
                $fractal = new Manager();
                $fractal->setSerializer(new DataArraySerializer);
                $resource = new Item($user, new User);
                $data = $fractal->createData($resource)->toArray()['data'];

                $data["status"] = "success";
                //$data["message"] = "Hi {$user->first_name}";

                $user->data(['last_activity' => date('Y-m-d H:i')]);
                $this->spot->mapper("App\User")->save($user);
            } else {
                $data["status"] = "error";
                $data["message"] = "El email y/o contraseña son incorrectas.";
            }

            return $response->withStatus(200)
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($data));  
        });

        $this->post("/signup", function ($request, $response, $arguments) {
                
            $body = $request->getParsedBody();
            $data = ["status" => "error"];

            if(empty($body['password'])){
                $body['password'] = strtolower(Base62::encode(random_bytes(10)));
            }

            $user = $this->spot->mapper("App\User")->first([
                'email' => $body['email']
            ]);

            if($user) {
                $data["status"] = "error";
                $data["message"] = "Esta cuenta ya está creada.<br><a href='/recover-password'>Recuperar cuenta</a>";
            } else {
                $hash = sha1($body['password'].getenv('APP_HASH_SALT'));
                $user = new User([
                    "email" => $body["email"], 
                    "first_name" => $body["first_name"],
                    "last_name" => $body["last_name"],
                    "role_id" => 1,
                    "enabled" => 1,
                    "password_hash" => $hash
                ]);
                
                $this->spot->mapper("App\User")->save($user);
                
                $body['readable_password'] = $body["password"];
                $body['email_encoded'] = Base62::encode($body["email"]);

                $sent = \send_email_template("EMAIL_WELCOME",$user,$body);

                $fractal = new Manager();
                $fractal->setSerializer(new DataArraySerializer);
                $resource = new Item($user, new User);
                $data = $fractal->createData($resource)->toArray()['data'];

                if($sent){
                    $data["status"] = "success";
                    $data["message"] = "User successfully created";
                } else {
                    $data["status"] = "error";
                    $data["message"] = "Error when sending email";
                }
            }

            return $response->withStatus(200)
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($data));
        });
        $this->get("/validates/{encoded}", function ($request, $response, $arguments) {

            $decoded = Base62::decode($request->getAttribute('encoded'));

            $user = $this->spot->mapper("App\User")->first([
                "email" => $decoded,
                'enabled' => 1
            ]);

            if( ! $user){
                $data["status"] = "error";
                $data["message"] = "No user found";
            } else {
                $body = $user->data(['validated' => 1]);
                $this->spot->mapper("App\User")->save($body);
                $data["status"] = "success";
                $data["message"] = "Account successfully validated.";
            }

            $view = new \Slim\Views\Twig('templates', [
                'cache' => false
            ]);

            $params = $request->getQueryParams();
            $data["redirect"] = null;

            if( ! empty($params['redirect'])){
                $data["redirect"] = getenv('APP_URL') . $params['redirect'];
            }

            $fractal = new Manager();
            $fractal->setSerializer(new DataArraySerializer);
            $resource = new Item($user, new User);
            $data = $fractal->createData($resource)->toArray();

            echo \login_redirect($data['data']);
            exit;
        });

        $this->post("/update-password", function ($request, $response, $arguments) {
            $body = $request->getParsedBody();
            $new_password = $body['password'];
            $token = $body['token']?:false;
            $data["status"] = "error";
            $data["message"] = "Invalid token";
            $data["token"] = $token;

            if($token){
                $user = $this->spot->mapper("App\User")->first([
                    "password_token" => $token,
                    'enabled' => 1
                ]);
            }

            if($user === false){
                $data['message'] = "Código de verificación inválido. Por favor chequee si no tiene una notificación mas reciente.";
            } else {
                //$password = strtolower(Base62::encode(random_bytes(16)));
                $body['password'] = $new_password;
                $body['email'] = $user->email;
                $body['first_name'] = $user->first_name;
                $hash = sha1($new_password.getenv('APP_HASH_SALT'));

                $user->data([
                    'password_hash' => $hash,
                    'password_token' => ""
                ]);

                $this->spot->mapper("App\User")->save($user);

                $sent = \send_email_template("EMAIL_PASSWORD_UPDATED",$user,$body);

                $fractal = new Manager();
                $fractal->setSerializer(new DataArraySerializer);
                $resource = new Item($user, new User);
                $data = $fractal->createData($resource)->toArray();
                $data["status"] = "success";
                $data["redirect_url"] = \login_redirect_url($data['data']);
            }

            return $response->withStatus(200)
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($data));    
        });

        $this->post("/recover-password", function ($request, $response, $arguments) {
            
            $body = $request->getParsedBody();

            $user = $this->spot->mapper("App\User")->first([
                'email' => trim($body['email']),
                'enabled' => 1
            ]);

            if( $user ){
                $password_token = strtolower(Base62::encode(random_bytes(16)));
                $body['password_token'] = $password_token;
                $user->data(['password_token' => $password_token]);
                $this->spot->mapper("App\User")->save($user);

                $sent = \send_email_template("EMAIL_PASSWORD_RECOVERY",$user,$body);

                if($sent['status']=='success'){
                    $data["status"] = 'success';
                    $data["message"] = "An e-mail was sent with instructions.";
                }
                
            } else {
                $data["status"] = "error";
                $data["message"] = "El email <b>{$body['email']}</b> no se encuentra registrado. ";
            }

            return $response->withStatus(200)
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($data));
        });        
    }); 
});
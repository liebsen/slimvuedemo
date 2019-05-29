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
use App\Refocus;


$app->group('/v2', function() {

    $this->group('/account', function() {

        /* cotizaciones */
        $this->post("/refocuses", function ($request, $response, $arguments) {
            // publicaciones
            if (false === $this->token->decoded->uid) {
                throw new ForbiddenException("Token expired or invalid.", 403);
            }
            
            $filter = [
                'user_id' => $this->token->decoded->uid,
                'complete' => 1,
                'deleted' => 0
            ];

            $order = [
                'updated' => "DESC"
            ];

            $mapper = $this->spot->mapper("App\Refocus")->all()
                ->where($filter)
                ->order($order);

            $fractal = new Manager();
            $fractal->setSerializer(new DataArraySerializer);
            $resource = new Collection($mapper, new Refocus);
            $data = $fractal->createData($resource)->toArray();

            return $response->withStatus(200)
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($data));    
        });

        $this->post("/refocuses/{guid}", function ($request, $response, $arguments) {
            
            $data = [];
            $guid = $request->getAttribute('guid');

            // publicaciones
            if (false === $this->token->decoded->uid) {
                throw new ForbiddenException("Token expired or invalid.", 403);
            }

            // detect lead
            $mapper = $this->spot->mapper("App\Refocus")
                ->where(['guid' => $guid])
                ->where(['completed' => 1])
                ->where(['deleted' => 0])
                ->first();

            $fractal = new Manager();
            $fractal->setSerializer(new DataArraySerializer);
            $resource = new Item($mapper, new Refocus);
            $data = $fractal->createData($resource)->toArray();

            return $response->withStatus(200)
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($data));    
        });

        $this->post('/dash', function ($request, $response, $args) {

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
                $data['status'] = "success";
                $user->data($body);
                $this->spot->mapper("App\User")->save($user);
            }

            return $response->withStatus(200)
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($data));    
        });
    });
});
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
use App\Config;
use App\Color;
use App\Colorant;
use App\Base;
use App\Texture;
use App\Section;
use App\Packing;
use App\Quote;
use App\QuoteFormula;
use App\QuoteItem;
use App\Machine;
use App\MachineType;
use App\MachineUnit;
use App\Formula;
use App\Email;
use App\UserType;
use App\UserColorant;
use App\UserTexture;

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

        $this->post("/formularcolor", function ($request, $response, $arguments) {

            if (false === $this->token->decoded->uid) {
                throw new ForbiddenException("Token expired or invalid.", 403);
            }


            $data['status'] = 'success';
            $data['message'] = [];
            $check = 0;

            $user = $this->spot->mapper("App\User")->first([
                'id' => $this->token->decoded->uid
            ]);

            $colorants = $this->spot->mapper("App\Colorant")->where([
                'enabled' => 1
            ]);

            $packs = $this->spot->mapper("App\Packing")->where([
                'enabled' => 1
            ]);

            $textures = $this->spot->mapper("App\Texture")->where([
                'enabled' => 1
            ]);

            $bases = $this->spot->mapper("App\Base")->where([
                'enabled' => 1
            ]);

            foreach ($textures as $texture) {
                foreach ($bases as $base) {
                    foreach ($packs as $pack) {
                        if(!$check && $pack->texture_type_id == $texture->type_id){
                            $exists = $this->spot->mapper("App\UserTexture")->first([
                                'user_id' => $this->token->decoded->uid,
                                'packing_id' => $pack->id,
                                'texture_id' => $texture->id,
                                'base_id' => $base->id,
                                'price >' => 0
                            ]);

                            if(!$exists) {
                                $data['status'] = 'danger';
                                $data['message'][] = "Por favor ingrese el costo de las <b>texturas</b> en Base de datos para cotizar.";
                                $check = 1;
                            }
                        }
                    }
                }
            }

            foreach ($colorants as $colorant) {
                $exists = $this->spot->mapper("App\UserColorant")->first([
                    'user_id' => $this->token->decoded->uid,
                    'colorant_id' => $colorant->id,
                    'price >' => 0
                ]);

                if(!$exists) {
                    $data['status'] = 'danger';
                    $data['message'][] = "Por favor ingrese el costo de todos los <b>colorantes</b> en Base de datos para cotizar.";
                    break;
                }
            }

            if(empty((float) $user->iva)){
                $data['status'] = 'danger';
                $data['message'][] = "Por favor ingrese el valor de <b>IVA</b> deseado para el cálculo de costos.";
            }

            if(empty((float) $user->margen)){
                $data['status'] = 'danger';
                $data['message'][] = "Por favor ingrese el <b>margen deseado</b> para el cálculo de costos en Base de datos para cotizar.";
            }

            if($data['status'] == 'success'){
                $ids = [];
                $mapper = $this->spot->mapper("App\Texture")
                    ->where(["enabled" => 1]);

                foreach ($mapper as $i => $item) {
                    $hascolor = $this->spot->mapper("App\Color")
                        ->where(['user_id' => [1,$this->token->decoded->uid]])
                        ->where(['texture_id' => $item->id])
                        ->count();
                    if($hascolor){
                        $ids[] = $item->id;
                    }
                }

                $mapper = $this->spot->mapper("App\Texture")
                    ->where(["id" => array_unique($ids)]);

                $fractal = new Manager();
                $fractal->setSerializer(new DataArraySerializer);
                $resource = new Collection($mapper, new Texture);
                $data['textures'] = $fractal->createData($resource)->toArray()['data'];
            } else {
                $data['message'][] = "<a class='has-text-info' href='/base-de-datos-para-cotizar'>Configurar mi base de datos ahora.</a>";
                $data['message'][] = "<a class='has-text-info' href='/docs/manual-del-usuario/index.html#-configure-su-base-de-datos-para-comenzar' target='_blank'>Aprenda mas sobre como Configurar tu base de datos accediendo a la documentación.</a>";
            }

            return $response->withStatus(200)
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($data));
        });

        $this->post("/formularcolordatos", function ($request, $response, $arguments) {

            if (false === $this->token->decoded->uid) {
                throw new ForbiddenException("Token expired or invalid.", 403);
            }

            $body = $request->getParsedBody();
            extract($body);

            if (empty($id) OR empty($code)) {
                throw new ForbiddenException("Not enough parameters.", 403);
            }
            
            $mapper = $this->spot->mapper("App\Color")
                ->where(['user_id' => [1,$this->token->decoded->uid]])
                ->where(['texture_id' => $id]);

            $fractal = new Manager();
            $fractal->setSerializer(new DataArraySerializer);
            $resource = new Collection($mapper, new Color);
            $data['colors'] = $fractal->createData($resource)->toArray()['data'];

            $mapper = $this->spot->mapper("App\UserType")
                ->where(['id >' => 9]); // not system users

            $fractal = new Manager();
            $fractal->setSerializer(new DataArraySerializer);
            $resource = new Collection($mapper, new UserType);
            $data['usertypes'] = $fractal->createData($resource)->toArray()['data'];

            $mapper = $this->spot->mapper("App\Packing")
                ->where(['texture_type_id' => $type_id])
                ->where(['enabled' => true]);

            $fractal = new Manager();
            $fractal->setSerializer(new DataArraySerializer);
            $resource = new Collection($mapper, new Packing);
            $data['packs'] = $fractal->createData($resource)->toArray()['data'];

            return $response->withStatus(200)
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($data));
        });


        $this->post("/packs", function ($request, $response, $arguments) {

            if (false === $this->token->decoded->uid) {
                throw new ForbiddenException("Token expired or invalid.", 403);
            }

            $body = $request->getParsedBody();
            extract($body);

            $texture = $this->spot->mapper("App\Texture")
                ->first(['id' => $texture_id]);

            $mapper = $this->spot->mapper("App\Packing")
                ->where(['texture_type_id' => $texture->type_id])
                ->where(['enabled' => true]);

            $fractal = new Manager();
            $fractal->setSerializer(new DataArraySerializer);
            $resource = new Collection($mapper, new Packing);
            $data = $fractal->createData($resource)->toArray()['data'];

            return $response->withStatus(200)
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($data));
        });
        
        $this->post("/basededatos", function ($request, $response, $arguments) {

            if (false === $this->token->decoded->uid) {
                throw new ForbiddenException("Token expired or invalid.", 403);
            }

            $body = $request->getParsedBody();
            extract($body);

            //var_dump($this->token->decoded->uid);
            $mapper = $this->spot->mapper("App\User")->first([
                'id' => $this->token->decoded->uid
            ]);

            $data['inputs']['iva'] = (float) $mapper->iva;
            $data['inputs']['margen'] = (float) $mapper->margen;

            /**/
            $packs = $this->spot->mapper("App\Packing")
                ->where(['enabled' => true]);

            $fractal = new Manager();
            $fractal->setSerializer(new DataArraySerializer);
            $resource = new Collection($packs, new Packing);
            $data['packs'] = $fractal->createData($resource)->toArray()['data'];
            /**/
            $colorants = $this->spot->mapper("App\Colorant")
                ->where(['enabled' => true]);

            $fractal = new Manager();
            $fractal->setSerializer(new DataArraySerializer);
            $resource = new Collection($colorants, new Colorant);
            $data['colorants'] = $fractal->createData($resource)->toArray()['data'];

            /**/
            $bases = $this->spot->mapper("App\Base")
                ->where(['enabled' => true]);

            $fractal = new Manager();
            $fractal->setSerializer(new DataArraySerializer);
            $resource = new Collection($bases, new Base);
            $data['bases'] = $fractal->createData($resource)->toArray()['data'];
            /**/
            $textures = $this->spot->mapper("App\Texture")
                ->where(['enabled' => true]);

            $fractal = new Manager();
            $fractal->setSerializer(new DataArraySerializer);
            $resource = new Collection($textures, new Texture);
            $data['textures'] = $fractal->createData($resource)->toArray()['data'];

            /**/

            foreach($colorants as $colorant){
                $data['inputs']['user_colorants'][$colorant->id] = 0;
            }

            foreach($packs as $pack){
                foreach($bases as $base){
                    foreach($textures as $texture){
                        $data['inputs']['user_textures'][$pack->id][$base->id][$texture->id] = 0;
                    }
                }
            }

            /**/
            $mapper = $this->spot->mapper("App\UserColorant")
                ->where(['user_id' => $this->token->decoded->uid])
                ->order(['colorant_id' => "ASC"]);

            foreach($mapper as $user_colorant){
                $data['inputs']['user_colorants'][$user_colorant->colorant_id] = (float) $user_colorant->price;
            }

           /**/
            $mapper = $this->spot->mapper("App\UserTexture")
                ->where(['user_id' => $this->token->decoded->uid])
                ->order(['packing_id' => "ASC",'base_id' => "ASC",'texture_id' => "ASC"]);

            foreach($mapper as $user_texture){
                $data['inputs']['user_textures'][$user_texture->packing_id][$user_texture->base_id][$user_texture->texture_id] = (float) $user_texture->price;
            }

            $data['dates'] = [];
            /**/
            $mapper = $this->spot->mapper("App\UserColorant")
                ->where(['user_id' => $this->token->decoded->uid])
                ->order(['updated' => 'DESC'])
                ->first();

            $data['dates']['colorants'] = $mapper ? $mapper->updated->format('j/n/y H:i') : '';

            /**/
            $mapper = $this->spot->mapper("App\UserTexture")
                ->where(['user_id' => $this->token->decoded->uid])
                ->order(['updated' => 'DESC'])
                ->first();

            $data['dates']['textures'] = $mapper ? $mapper->updated->format('j/n/y H:i') : '';

            return $response->withStatus(200)
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($data));
        });

        // Trust me this method should be put if the current server configuration would have allowed me.
        $this->post("/basededatosp", function ($request, $response, $arguments) {

            if (false === $this->token->decoded->uid) {
                throw new ForbiddenException("Token expired or invalid.", 403);
            }

            $body = $request->getParsedBody();
            extract($body);

            if(empty($user_colorants)||empty($user_textures)){
                throw new ForbiddenException("Not enough parameters.", 403);
            }

            foreach($user_colorants as $colorant_id => $price){

                $payload = [
                    'user_id' => $this->token->decoded->uid,
                    'colorant_id' => $colorant_id
                ];

                $mapper = $this->spot->mapper("App\UserColorant")->first($payload);

                if(!$mapper){
                    $mapper = new UserColorant();
                    $mapper->data($payload);
                } 

                if($mapper->price <> $price){
                    $mapper->data([
                        'price' => $price,
                        'updated' => new DateTime("now")
                    ]);
                }

                $this->spot->mapper("App\UserColorant")->save($mapper);
            }

            foreach($user_textures as $packing_id => $pack){
                foreach($pack as $base_id => $base){
                    foreach($base as $texture_id => $price){
                        $payload = [
                            'user_id' => $this->token->decoded->uid,
                            'packing_id' => $packing_id,
                            'base_id' => $base_id,
                            'texture_id' => $texture_id
                        ];

                        $mapper = $this->spot->mapper("App\UserTexture")->first($payload);

                        if(!$mapper){
                            $mapper = new UserTexture();
                            $mapper->data($payload);
                        } 

                        if($mapper->price <> $price){
                            $mapper->data([
                                'price' => $price,
                                'updated' => new DateTime("now")
                            ]);                            
                        }

                        $this->spot->mapper("App\UserTexture")->save($mapper);
                    }
                }
            }

            $mapper = $this->spot->mapper("App\User")->first([
                'id' => $this->token->decoded->uid
            ]);

            $mapper->data([
                'margen' => (float) $margen,
                'iva' => (float) $iva
            ]);

            $this->spot->mapper("App\User")->save($mapper);

            $data['dates'] = [];
            /**/
            $mapper = $this->spot->mapper("App\UserColorant")
                ->where(['user_id' => $this->token->decoded->uid])
                ->order(['updated' => 'DESC'])
                ->first();

            $data['dates']['colorants'] = $mapper->updated->format('j/n/y H:i');

            /**/
            $mapper = $this->spot->mapper("App\UserTexture")
                ->where(['user_id' => $this->token->decoded->uid])
                ->order(['updated' => 'DESC'])
                ->first();

            $data['dates']['textures'] = $mapper->updated->format('j/n/y H:i');

            $data['status'] = 'success';
            return $response->withStatus(200)
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($data));            

        });

        $this->post("/cargarformulaspropias", function ($request, $response, $arguments) {

            if (false === $this->token->decoded->uid) {
                throw new ForbiddenException("Token expired or invalid.", 403);
            }

            $body = $request->getParsedBody();
            extract($body);

            /**/
            $machines = $this->spot->mapper("App\Machine")->where([            
                'user_id' => $this->token->decoded->uid
            ]);

            $fractal = new Manager();
            $fractal->setSerializer(new DataArraySerializer);
            $resource = new Collection($machines, new Machine);
            $data['machines'] = $fractal->createData($resource)->toArray()['data'];

            /**/
            $mapper = $this->spot->mapper("App\Colorant")
                ->where(['enabled' => true]);

            $fractal = new Manager();
            $fractal->setSerializer(new DataArraySerializer);
            $resource = new Collection($mapper, new Colorant);
            $data['colorants'] = $fractal->createData($resource)->toArray()['data'];

            /**/
            $mapper = $this->spot->mapper("App\Base")
                ->where(['enabled' => true]);

            $fractal = new Manager();
            $fractal->setSerializer(new DataArraySerializer);
            $resource = new Collection($mapper, new Base);
            $data['bases'] = $fractal->createData($resource)->toArray()['data'];
            /**/
            $mapper = $this->spot->mapper("App\Texture")
                ->where(['enabled' => true]);

            $fractal = new Manager();
            $fractal->setSerializer(new DataArraySerializer);
            $resource = new Collection($mapper, new Texture);
            $data['textures'] = $fractal->createData($resource)->toArray()['data'];


            if(empty($mac_id)){
                $mac_id = $machines[0]->id;
            }

            if(!empty($mac_id)){

                $machine = $this->spot->mapper("App\Machine")->first([            
                    'user_id' => $this->token->decoded->uid,
                    'id' => $mac_id
                ]);

                $fractal = new Manager();
                $fractal->setSerializer(new DataArraySerializer);
                $resource = new Item($machine, new Machine);
                $data['machine'] = $fractal->createData($resource)->toArray()['data'];
            }


            /**/
            $mapper = $this->spot->mapper("App\MachineUnit")
                ->where(['type_id' => $machine->type->id]);

            foreach($mapper as $item) {
                $data['units'][] = $item->code;
            }

            return $response->withStatus(200)
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($data));
        });


        $this->post("/cargarformulaspropiasp", function ($request, $response, $arguments) {

            if (false === $this->token->decoded->uid) {
                throw new ForbiddenException("Token expired or invalid.", 403);
            }

            $body = $request->getParsedBody();
            extract($body);

            $machine = $this->spot->mapper("App\Machine")->first([            
                'user_id' => $this->token->decoded->uid,
                'id' => $mac_id
            ]);

            $mapper = new Color();
            $mapper->data([
                'user_id' => $this->token->decoded->uid,
                'texture_id' => $texture_id,
                'base_id' => $base_id,
                'title' => $title
            ]);

            $color_id = $this->spot->mapper("App\Color")->save($mapper);

            $auto = strstr(strtolower($machine->type->title),"auto");
            $manual = strstr(strtolower($machine->type->title),"manual");

            foreach($units as $colorant_id => $units){

                $colorant = $this->spot->mapper("App\Colorant")->first([            
                    'id' => $colorant_id
                ]);

                $g = 0;

                // obtenemos el valor en gramos
                if($auto){
                    if(!empty($units['g'])) {
                        $g = (float) $units['g'];
                    } else if(!empty($units['ml'])) {
                        $g = (float) $colorant->density / $units['ml'];
                    }
                } else if($manual){
                    $g = \convert_g((float) $machine->ounce->ml, (int) $machine->pulse->quantity, (float) $colorant->density, (float) $units['y'], (float) $units['p'], (float) $units['f']);
                }

                if($g){
                    $payload = [
                        'color_id' => $color_id,
                        'colorant_id' => $colorant_id,
                        'unit' => 'g',
                        'amount' => number_format($g,2)
                    ];

                    $mapper = $this->spot->mapper("App\Formula")->first($payload);

                    if(!$mapper){
                        $mapper = new Formula();
                        $mapper->data($payload);
                    } 

                    $this->spot->mapper("App\Formula")->save($mapper);
                }
            }

            $data['status'] = 'success';
            return $response->withStatus(200)
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($data));            
        });


        $this->post("/quotep", function ($request, $response, $arguments) {

            if (false === $this->token->decoded->uid) {
                throw new ForbiddenException("Token expired or invalid.", 403);
            }

            $payload = $request->getParsedBody();
            extract($payload);

            if (false === $payload) {
                throw new ForbiddenException("Not enough parameters.", 403);
            }

            if (empty($color_id)) {
                throw new ForbiddenException("No color provided.", 403);
            }

            if(empty($discount)){
                $discount = 0;
            }

            // calculate costs for each user exclusively 

            // find amount of colorants
            $items = [];
            $data = [];
            $formulation = [];
            $subtotal = 0;
            $total = 0;
            $qty = ceil((float) $qty);

            $payload['qty'] = $qty;

            if(empty($kg)){
                $mapper = $this->spot->mapper("App\Packing")->first([
                    'id' => $pack_id
                ]);

                $payload['kg'] = intval($mapper->kg) * $qty;
            }   

            // gather some base information
            $user = $this->spot->mapper("App\User")->first([
                'id' => $this->token->decoded->uid
            ]);

            $color = $this->spot->mapper("App\Color")->first([
                'user_id' => [1,$this->token->decoded->uid],
                'id' => $color_id
            ]);

            $formulas = $this->spot->mapper("App\Formula")->where([
                'unit' => 'g',
                'color_id' => $color_id
            ]);

            $mapper_texture = $this->spot->mapper("App\Texture")->first([            
                'id' => $texture_id
            ]);

            $colorant_pack_ml = (int) getenv('COLORANT_PACK_ML');
            $colorants_total = 0;
            $colorants_ml = 0;

            // calc
            foreach ($formulas as $formula) {
                // find out colorants costs
                $colorant = $this->spot->mapper("App\UserColorant")->first([
                    'user_id' => $this->token->decoded->uid,
                    'colorant_id' => $formula->colorant_id
                ]);

                $ml = (float) $formula->amount / $colorant->colorant->density;

                if($colorant){
                    // Enmascaramos los detalles de colorantes
                    $colorants_total+= \get_price_rounded((float) $colorant->price / $colorant_pack_ml * $ml * ((float) $payload['kg'] / (float) $mapper_texture->colorant_unit));
                    $colorants_ml+=$ml;
                }

                $formulation[]= (object) [
                    'code' => $formula->colorant->code,
                    'description' => $formula->colorant->description,
                    'density' => $formula->colorant->density,
                    'hexcode' => $formula->colorant->hexcode,
                    'amount' => $formula->amount
                ];
            }

            $items[]= (object)[
                'title' => 'Colorante',
                'qty' => $colorants_ml."ml",
                'unit' => "",
                'amount' => \get_price_rounded((float) $colorants_total * ($user->margen / 100 + 1) * (1 - $discount / 100))
            ];

            // find out textures costs

            $texture = $this->spot->mapper("App\UserTexture")->first([
                'user_id' => $this->token->decoded->uid,
                'base_id' => $color->base_id,
                'packing_id' => $pack_id,
                'texture_id' => $texture_id
            ]);

            if($texture){
                $unit_price = round($texture->price * ($user->margen / 100 + 1) * (1 - $discount / 100));
                $payload['unit_price'] = $unit_price;
                $items[]= (object) [
                    'title' => implode(' ',['Texturante',$mapper_texture->code]),
                    'qty' => $qty,
                    'unit' => $unit_price,
                    'amount' => \get_price_rounded((float) $texture->price * $qty * ($user->margen / 100 + 1) * (1 - $discount / 100)) // apply margin and discount
                ];
            }

            foreach($items as $item){
                $subtotal+= $item->amount;
            }

            $total = $subtotal * ($user->iva / 100 + 1);

            // format
            if(!empty($id)){
                $mapper = $this->spot->mapper("App\Quote")->first([
                    'id' => $id
                ]);
            } else {
                $mapper = new Quote();
                $payload['base_id'] = $color->base_id;
                $payload['bases'] = $color->base->title;
            }

            $payload['subtotal'] = \get_price_rounded($subtotal);
            $payload['total'] = \get_price_rounded($total);

            if(!empty($first_name) && !empty($last_name)){
                $payload['first_name'] = ucfirst($first_name);
                $payload['last_name'] = ucfirst($last_name);
                $payload['customer'] = ucwords(implode(' ',[$first_name,$last_name]));
            }

            $mapper->data($payload);

            $lastid = $this->spot->mapper("App\Quote")->save($mapper);        

            if(empty($id)){
                $mapper->data([
                    'uuid' => \generate_uuid($lastid)
                ]);

                $this->spot->mapper("App\Quote")->save($mapper);        
            }

            // save auxiliary data
            $this->spot->mapper("App\QuoteItem")->where([
                'quote_id' => $lastid
            ])->delete();

            foreach ($items as $item) {
                $this->spot->mapper("App\QuoteItem")->save(new App\QuoteItem([
                    'quote_id' => $lastid,
                    'title' => $item->title,
                    'qty' => $item->qty,
                    'unit' => $item->unit,
                    'amount' => $item->amount
                ]));
            }

            $this->spot->mapper("App\QuoteFormula")->where([
                'quote_id' => $lastid
            ])->delete();

            foreach ($formulation as $formula) {
                $this->spot->mapper("App\QuoteFormula")->save(new App\QuoteFormula([
                    'quote_id' => $lastid,
                    'code' => $formula->code,
                    'description' => $formula->description,
                    'density' => $formula->density,
                    'hexcode' => $formula->hexcode,
                    'amount' => $formula->amount
                ]));
            }

            $fractal = new Manager();
            $fractal->setSerializer(new DataArraySerializer);
            $resource = new Item($mapper, new Quote);
            $data = $fractal->createData($resource)->toArray()['data'];

            unset($data['created']);
            unset($data['texture_hexcode']);

            return $response->withStatus(200)
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($data));
        });

        $this->post("/quotes", function ($request, $response, $arguments) {

            if (false === $this->token->decoded->uid) {
                throw new ForbiddenException("Token expired or invalid.", 403);
            }

            $body = $request->getParsedBody();
            extract($body);

            $filter = ['customer <>' => ""];

            if(!empty($customer)){
                $filter = ['customer' => $customer];
            }

            // quotes
            $mapper = $this->spot->mapper("App\Quote")
                ->where(['user_id' => $this->token->decoded->uid])
                ->where($filter)
                ->order(['updated' => 'DESC']);

            $fractal = new Manager();
            $fractal->setSerializer(new DataArraySerializer);
            $resource = new Collection($mapper, new Quote);
            $data['quotes'] = $fractal->createData($resource)->toArray()['data'];

            // customers
            $mapper = $this->spot->mapper("App\Quote")
                ->where(['user_id' => $this->token->decoded->uid])
                ->order(['customer' => 'ASC']);

            foreach($mapper as $quote){
                if(!in_array($quote->customer,$data['customers'])){
                    $data['customers'][] = $quote->customer;
                }
            }

            return $response->withStatus(200)
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($data));

        });

        $this->post("/quote", function ($request, $response, $arguments) {

            if (false === $this->token->decoded->uid) {
                throw new ForbiddenException("Token expired or invalid.", 403);
            }

            $body = $request->getParsedBody();
            extract($body);

            $mapper = $this->spot->mapper("App\Quote")->first([            
                'uuid' => $uuid
            ]);

            $fractal = new Manager();
            $fractal->setSerializer(new DataArraySerializer);
            $resource = new Item($mapper, new Quote);
            $data = $fractal->createData($resource)->toArray()['data'];

            return $response->withStatus(200)
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($data));
        });

        $this->post("/colors", function ($request, $response, $arguments) {

            if (false === $this->token->decoded->uid) {
                throw new ForbiddenException("Token expired or invalid.", 403);
            }

            $body = $request->getParsedBody();
            extract($body);

            // colors
            $mapper = $this->spot->mapper("App\Color")
                ->where(['user_id' => $this->token->decoded->uid])
                ->order(['created' => 'DESC']);

            $fractal = new Manager();
            $fractal->setSerializer(new DataArraySerializer);
            $resource = new Collection($mapper, new Color);
            $data['colors'] = $fractal->createData($resource)->toArray()['data'];

            return $response->withStatus(200)
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($data));

        });

        $this->post("/colord", function ($request, $response, $arguments) {

            if (false === $this->token->decoded->uid) {
                throw new ForbiddenException("Token expired or invalid.", 403);
            }

            $body = $request->getParsedBody();
            extract($body);

            $formulas = $this->spot->mapper("App\Formula")->where([
                'color_id' => $id
            ]);

            foreach($formulas as $formula){
                $this->spot->mapper("App\Formula")->delete($formula);
            }

            $color = $this->spot->mapper("App\Color")->first([
                'user_id' => $this->token->decoded->uid,
                'id' => $id
            ]);

            if($color){
                $this->spot->mapper("App\Color")->delete($color);
            }


            $data['status'] = 'success';

            return $response->withStatus(200)
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($data));

        });

        $this->post("/comments", function ($request, $response, $arguments) {

            if (false === $this->token->decoded->uid) {
                throw new ForbiddenException("Token expired or invalid.", 403);
            }

            $body = $request->getParsedBody();
            extract($body);

            if (empty($uuid)) {
                throw new ForbiddenException("Not enough parameters.", 403);
            }

            /**/
            $mapper = $this->spot->mapper("App\Quote")->first([
                'user_id' => $this->token->decoded->uid,
                'uuid' => $uuid
            ]);

            if (false === $mapper) {
                throw new ForbiddenException("Not such quote.", 403);
            }

            $mapper->data(['comments' => trim($comments)]);
            $this->spot->mapper("App\Quote")->save($mapper);

            $data['status'] = 'success';

            return $response->withStatus(200)
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($data));
        });

        $this->post("/color", function ($request, $response, $arguments) {

            if (false === $this->token->decoded->uid) {
                throw new ForbiddenException("Token expired or invalid.", 403);
            }

            $body = $request->getParsedBody();
            extract($body);

            /**/
            $quote = $this->spot->mapper("App\Quote")->first([            
                'uuid' => $uuid
            ]);

            if (false === $quote) {
                throw new ForbiddenException("Not such quote.", 403);
            }

            $fractal = new Manager();
            $fractal->setSerializer(new DataArraySerializer);
            $resource = new Item($quote, new Quote);
            $data['quote'] = $fractal->createData($resource)->toArray()['data'];

            /**/
            $machines = $this->spot->mapper("App\Machine")->where([            
                'user_id' => $this->token->decoded->uid
            ]);

            $fractal = new Manager();
            $fractal->setSerializer(new DataArraySerializer);
            $resource = new Collection($machines, new Machine);
            $data['machines'] = $fractal->createData($resource)->toArray()['data'];

            /**/
            $formulas = [];
            $data['machine'] = [];

            if(empty($mac_id)){
                $mac_id = $machines[0]->id;
            }

            if(!empty($mac_id)){

                $machine = $this->spot->mapper("App\Machine")->first([            
                    'id' => $mac_id
                ]);

                $fractal = new Manager();
                $fractal->setSerializer(new DataArraySerializer);
                $resource = new Item($machine, new Machine);
                $data['machine'] = $fractal->createData($resource)->toArray()['data'];

                /**/
                $auto = strstr(strtolower($machine->type->title),"auto");
                $manual = strstr(strtolower($machine->type->title),"manual");
                $formulation = $this->spot->mapper("App\QuoteFormula")->where([            
                    'quote_id' => $quote->id
                ]);

                foreach ($formulation as $i => $formula) {
                    $ml = (float) $formula->amount / $formula->density;

                    if(empty($formulas[$i])){
                        $formulas[$i] = [
                            'colorant' => $formula->code,
                            'description' => $formula->description,
                            'hexcode' => $formula->hexcode,
                            'amounts' => []
                        ];
                    }

                    if($manual){
                        $formulas[$i]['amounts'] = \convert_machine($ml * (int) $quote->packs / $quote->texture->colorant_unit,$machine->ounce->ml,$machine->pulse->quantity,$machine->fraction->quantity);
                    } else if($auto) {
                        $formulas[$i]['amounts']['g'] = number_format($formula->amount * (int) $quote->packs / $quote->texture->colorant_unit,2);
                        $formulas[$i]['amounts']['ml'] = number_format($ml * (int) $quote->packs / $quote->texture->colorant_unit,2);
                    }
                }
            }

            // clear if necessary

            foreach($formulas as $i => $formula){
                $hasanyvalue = 0;
                foreach ($formula['amounts'] as $amount) {
                    if($amount > 0){
                        $hasanyvalue = 1;
                    }
                }

                if(!$hasanyvalue){
                    unset($formulas[$i]);
                }
            }

            $data['machine']['units'] = $auto ? ['g','ml'] : ['y','pulsos','fracción'];
            $data['formulas'] = $formulas;

            return $response->withStatus(200)
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($data));
        });

        $this->post("/formula", function ($request, $response, $arguments) {

            if (false === $this->token->decoded->uid) {
                throw new ForbiddenException("Token expired or invalid.", 403);
            }

            $body = $request->getParsedBody();
            extract($body);

            /**/
            $machines = $this->spot->mapper("App\Machine")->where([            
                'user_id' => $this->token->decoded->uid
            ]);

            $fractal = new Manager();
            $fractal->setSerializer(new DataArraySerializer);
            $resource = new Collection($machines, new Machine);
            $data['machines'] = $fractal->createData($resource)->toArray()['data'];

            if(empty($mac_id)){
                $mac_id = $machines[0]->id;
            }

            if(!empty($mac_id)){
                $machine = $this->spot->mapper("App\Machine")->first([            
                    'user_id' => $this->token->decoded->uid,
                    'id' => $mac_id
                ]);

                $fractal = new Manager();
                $fractal->setSerializer(new DataArraySerializer);
                $resource = new Item($machine, new Machine);
                $data['machine'] = $fractal->createData($resource)->toArray()['data'];
            }

            /**/
            $mapper = $this->spot->mapper("App\Color")->first([
                'user_id' => $this->token->decoded->uid,
                'id' => $id
            ]);

            if (false === $mapper) {
                throw new ForbiddenException("Fórmula no encontrada.", 403);
            }

            $fractal = new Manager();
            $fractal->setSerializer(new DataArraySerializer);
            $resource = new Item($mapper, new Color);
            $data['color'] = $fractal->createData($resource)->toArray()['data'];

            $formulas = [];

            $mapper = $this->spot->mapper("App\Formula")
                ->where(['color_id' => $id])
                ->where(['unit' => 'g']);

            $auto = strstr(strtolower($machine->type->title),"auto");
            $manual = strstr(strtolower($machine->type->title),"manual");

            foreach ($mapper as $formula) {
                $ml = (float) $formula->amount / $formula->colorant->density;
                if(empty($formulas[$formula->colorant_id])){
                    $formulas[$formula->colorant_id] = [
                        'colorant' => $formula->colorant->code,
                        'description' => $formula->colorant->description,
                        'hexcode' => $formula->colorant->hexcode,
                        'amounts' => []
                    ];
                }

                if($manual){
                    $formulas[$formula->colorant_id]['amounts'] = \convert_machine($ml,$machine->ounce->ml,$machine->pulse->quantity,$machine->fraction->quantity);
                } else if($auto) {
                    $formulas[$formula->colorant_id]['amounts']['g'] = number_format($formula->amount,2);
                    $formulas[$formula->colorant_id]['amounts']['ml'] = number_format($ml,2);
                }
            }

            $data['formulas'] = $formulas;
            $data['machine']['units'] = $auto ? ['g','ml'] : ['y','pulsos','fracción'];

            return $response->withStatus(200)
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($data));
        });

        $this->post("/excel", function ($request, $response, $arguments) {

            if (false === $this->token->decoded->uid) {
                throw new ForbiddenException("Token expired or invalid.", 403);
            }

            $body = $request->getParsedBody();
            extract($body);

            if (empty($since) OR empty($until)) {
                throw new ForbiddenException("Not enough parameters.", 403);
            }

            $filter = ['customer <>' => ""];

            if(!empty($customer)){
                $filter = ['customer' => $customer];
            }

            $since2 = implode('-',array_reverse(explode('-',$since)));
            $until2 = implode('-',array_reverse(explode('-',$until)));

            $mapper = $this->spot->mapper("App\Quote")
                ->where(['user_id' => $this->token->decoded->uid])
                ->where($filter)
                ->where(['created >' => date('Y-m-d H:i', strtotime($since2))])
                ->where(['created <' => date('Y-m-d 23:59:59', strtotime($until2))]);

            $fractal = new Manager();
            $fractal->setSerializer(new DataArraySerializer);
            $resource = new Collection($mapper, new Quote);
            $quotes = $fractal->createData($resource)->toArray()['data'];
            $trs = [];
            $data =  "";

            $fields = [
                'id' => "#",
                //'uuid' => "UUID",
                'customer' => "Cliente",
                'email' => "Email",
                'phone' => "Tel",
                'texture' => "Textura",
                'base' => "Base",
                'pack' => "Envase",
                'qty' => "Cant",
                'subtotal' => "Subtotal ARS",
                'total' => "Total ARS",
                'created' => "Fecha"
            ];

            $header = implode(',',array_values($fields));

            foreach ($quotes as $quote) {
                $tds = [];
                foreach($fields as $field => $label){
                    $tds[] = $quote[$field];
                }
                $trs[] = implode(',',$tds);
            }

            $data.= $header;
            $data.= "\n";
            $data.= implode("\n",$trs);
            $data.= "\n";

            header('Content-Description: File Transfer'); 
            header('Content-Type: application/octet-stream'); 
            header(`Content-Disposition: attachment; filename="cotizaciones.csv"`);
            header('Content-Transfer-Encoding: binary'); 

            echo $data;
        });

        $this->post("/quote2pdf/{uuid}", function ($request, $response, $arguments) {

            if (false === $this->token->decoded->uid) {
                throw new ForbiddenException("Token expired or invalid.", 403);
            }

            $uuid = $request->getAttribute('uuid');

            header('Content-Description: File Transfer'); 
            header('Content-Type: application/octet-stream'); 
            header(`Content-Disposition: attachment; filename="{$uuid}.pdf"`);
            header('Content-Transfer-Encoding: binary'); 

            echo \quote2pdf($uuid,$this->token->decoded->uid);
        });

        $this->post("/color2pdf/{uuid}/{macid}", function ($request, $response, $arguments) {

            if (false === $this->token->decoded->uid) {
                throw new ForbiddenException("Token expired or invalid.", 403);
            }

            $uuid = $request->getAttribute('uuid');
            $macid = $request->getAttribute('macid');

            if(empty($macid)||empty($uuid)){
                throw new ForbiddenException("No parameters recieved.", 403);
            }

            header('Content-Description: File Transfer'); 
            header('Content-Type: application/octet-stream'); 
            header(`Content-Disposition: attachment; filename="{$uuid}.pdf"`);
            header('Content-Transfer-Encoding: binary'); 

            echo \color3pdf($uuid,$macid,$this->token->decoded->uid);
        });

        $this->post("/formula3pdf/{colid}/{macid}", function ($request, $response, $arguments) {

            if (false === $this->token->decoded->uid) {
                throw new ForbiddenException("Token expired or invalid.", 403);
            }

            $colid = $request->getAttribute('colid');
            $macid = $request->getAttribute('macid');

            if(empty($macid)||empty($colid)){
                throw new ForbiddenException("No parameters recieved.", 403);
            }

            header('Content-Description: File Transfer'); 
            header('Content-Type: application/octet-stream'); 
            header(`Content-Disposition: attachment; filename="{$uuid}.pdf"`);
            header('Content-Transfer-Encoding: binary'); 

            echo \formula3pdf($colid,$macid,$this->token->decoded->uid);
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
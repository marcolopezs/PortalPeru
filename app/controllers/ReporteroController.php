<?php

use PortalPeru\Entities\User;
use PortalPeru\Entities\UserProfile;

class ReporteroController extends BaseController {

    public function register()
    {
        return View::make('reportero.register');
    }

    public function registerCreate()
    {
        $rulesUser = [
            'email' => 'required|email|unique:users,email',
            'password' => 'required|confirmed',
            'password_confirmation' => 'required'
        ];

        $rulesProfile = [
            'nombre' => 'required',
            'apellidos' => 'required',
            'telefono' => 'required',
            'documento_tipo' => 'required',
            'documento_numero' => 'required',
            'direccion' => 'required',
        ];

        $dataUser = Input::only('email','password','password_confirmation');
        $dataProfile = Input::only('nombre','apellidos','telefono','documento_tipo','documento_numero','direccion');

        $validatorUser = Validator::make($dataUser, $rulesUser);
        $validatorProfile = Validator::make($dataProfile, $rulesProfile);

        if($validatorUser->passes() and $validatorProfile->passes())
        {
            $user = new User($dataUser);
            $user->type = 'reportero';
            $user->activacion = 1;
            $user->save();

            $emailUser = User::whereEmail(Input::get('email'))->first();
            $actCodigo = CodigoAleatorio(25,true, true, false);

            $userProfile = new UserProfile($dataProfile);
            $userProfile->user_id = $emailUser->id;
            $userProfile->activacion_codigo = $actCodigo;
            $userProfile->save();

            /*
            $datosCorreo = [
                'nombre' => Input::get('nombre'),
                'apellidos' => Input::get('apellidos'),
                'codigo' => $actCodigo
            ];

            $fromEmail = 'no-reply@portalperu.pe';
            $fromNombre = 'Portal Perú';

            Mail::send('emails.reportero-register', $datosCorreo, function($message) use ($fromNombre, $fromEmail){
                $message->to(Input::get('email'), Input::get('nombre'));
                $message->from($fromEmail, $fromNombre);
                $message->subject('Portal Perú - Activar cuenta');
            });

            */

            $message = 'verify';

            //REDIRECCIONAR A PAGINA PARA VER DATOS
            return View::make('reportero.verify', compact('message'));
        }
        else
        {
            return Redirect::back()->with('errors', array_merge_recursive($validatorUser->messages()->toArray(), $validatorProfile->messages()->toArray()))->withInput();
        }
    }

    public function verifyView()
    {
        return View::make('reportero.verify');
    }

    public function verify($codigo)
    {
        $userVerify = UserProfile::whereActivacionCodigo($codigo)->first();
        $user = User::whereId($userVerify->user_id)->first();

        if($userVerify->activacion == 0)
        {
            $user->activacion = 1;
            $user->save();
            $message = 'verify-active';
        }
        elseif($userVerify->activacion == 1)
        {
            $message = 'verify-noactive';
        }

        return View::make('reportero.verify', compact('message'));
    }

    public function loginView()
    {
        return View::make('reportero.login');
    }

    public function logout()
    {
        Auth::logout();
        return Redirect::route('reportero.login');
    }

    public function home()
    {
        return View::make('reportero.admin.home');
    }
} 
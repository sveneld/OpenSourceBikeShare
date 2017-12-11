<?php

namespace BikeShare\Http\Controllers\User\Map;

use BikeShare\Domain\User\User;
use BikeShare\Http\Controllers\Controller;
use BikeShare\Http\Services\AppConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MapController extends Controller
{
    /**
     * @var AppConfig
     */
    private $appConfig;

    public function __construct()
    {
        $this->appConfig = app(AppConfig::class);
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $isloggedin = Auth::check();
        $isadmin = $user && $isloggedin && $user->hasRole('admin');
        $iscreditenabled = $this->appConfig->isCreditEnabled();
        $issmssystemenabled = $this->appConfig->isSmsEnabled();
        $systemlat="48.148154"; // default map center point - latitude
        $systemlong="17.117232"; // default map center point - longitude
        $systemzoom= "15"; // default map zoom
        $systemname=$this->appConfig->getSystemName();
        $systemrules=$this->appConfig->getSystemRules();

        $currency = $this->appConfig->getCreditCurrency();

        // TODO: 0/1/2 depending on result
        $error = 0;

        return view('user.index',
            compact(
                'user',
                'error',
                'currency',
                'isloggedin',
                'isadmin',
                'iscreditenabled',
                'issmssystemenabled',
                'systemrules',
                'systemname',
                'systemlat',
                'systemlong',
                'systemzoom'));
    }

    public function login(Request $request){

        $request->validate([
            'number' => 'required',
            'password' => 'required',
        ]);

        $dispatcher = app('Dingo\Api\Dispatcher');

        try {
            $apiResponse = $dispatcher->post('api/auth/authenticate', [
                'phone_number' => $request->number,
                'password' => $request->password
            ]);
            return back()->cookie('token', $apiResponse['token']);
        } catch (HttpException $exception){
            if ($exception->getStatusCode() === 401){
                return "unauthenticated";
            }
        }
    }

    public function logout(Request $request){
        return back()->cookie('token');
    }
}
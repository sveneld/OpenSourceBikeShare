<?php

namespace BikeShare\Http\Controllers\User\Map;

use BikeShare\Domain\User\User;
use BikeShare\Http\Controllers\Controller;
use BikeShare\Http\Services\AppConfig;
use Illuminate\Support\Facades\Auth;

class MapController extends Controller
{
    /**
     * @var AppConfig
     */
    private $appConfig;

    /**
     * @var User
     */
    private $user;


    public function __construct(User $user)
    {
        $this->appConfig = app(AppConfig::class);
        $this->user = $user;
    }

    public function index()
    {
        $user = $this->user;
        $isloggedin = Auth::check();
        $isadmin = $isloggedin && $this->user->hasRole('admin');
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
}
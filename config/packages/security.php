<?php

declare(strict_types=1);

use BikeShare\App\Security\TokenProvider;
use BikeShare\App\Security\UserProvider;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Config\SecurityConfig;

return function (SecurityConfig $security) {
    $security
        ->passwordHasher(PasswordAuthenticatedUserInterface::class)
        ->algorithm('sha512')
        ->encodeAsBase64(false)
        ->iterations(1);

    $security
        ->provider('app_user_provider')
        ->id(UserProvider::class);

    $security
        ->firewall('dev')
        ->pattern('^/(_(profiler|wdt)|css|images|js)/')
        ->security(false);

    $mainFirewall = $security->firewall('main');
    $mainFirewall->anonymous();
    $mainFirewall
        ->formLogin()
        ->usernameParameter('number')
        ->passwordParameter('password')
        ->loginPath('login')
        ->checkPath('login');
    $mainFirewall
        ->logout()
        ->path('logout')
        ->target('/');
    $mainFirewall
        ->rememberMe()
        ->secret('%kernel.secret%')
        ->lifetime(604800) // 1 week in seconds
        ->tokenProvider(
            [
                'service' => TokenProvider::class,
            ]
        );

    $security->roleHierarchy('ROLE_ADMIN', ['ROLE_USER']);
    $security->roleHierarchy('ROLE_SUPER_ADMIN', ['ROLE_ADMIN']);

    $security->accessControl()
        ->path('^/admin')
        ->roles(['ROLE_ADMIN']);

    $security
        ->accessControl()
        ->path('^/login$')
        ->roles(['IS_AUTHENTICATED_ANONYMOUSLY']);
    $security
        ->accessControl()
        ->path('^/(sms/)?receive.php$')
        ->roles(['IS_AUTHENTICATED_ANONYMOUSLY']);
    $security
        ->accessControl()
        ->path('^/register.php$')
        ->roles(['IS_AUTHENTICATED_ANONYMOUSLY']);
    $security
        ->accessControl()
        ->path('^/agree.php$')
        ->roles(['IS_AUTHENTICATED_ANONYMOUSLY']);
    $security
        ->accessControl()
        ->path('^/resetPassword$')
        ->roles(['IS_AUTHENTICATED_ANONYMOUSLY']);
    $security
        ->accessControl()
        ->path('^/command.php$')
        ->roles(['IS_AUTHENTICATED_ANONYMOUSLY']);

    $security
        ->accessControl()
        ->path('^/')
        ->roles(['ROLE_USER']);
};
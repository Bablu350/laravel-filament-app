<?php
declare(strict_types=1);

$cfg['blowfish_secret'] = 'JOFw435365IScA&Q!cDugr!lSfuAz*OW';

$cfg['Servers'][1]['auth_type'] = 'cookie';
$cfg['Servers'][1]['host'] = 'ep-wandering-sky-a5kws55j.aws-us-east-2.pg.laravel.cloud:5432'; // Replace with your remote host
$cfg['Servers'][1]['compress'] = false;
$cfg['Servers'][1]['AllowNoPassword'] = false;
$cfg['Servers'][1]['LoginCookieRecall'] = true; // Enable server dropdown

// $cfg['Servers'][1]['auth_type'] = 'cookie';
// $cfg['Servers'][1]['host'] = 'localhost:3306'; // Replace with your remote host
// $cfg['Servers'][1]['compress'] = false;
// $cfg['Servers'][1]['AllowNoPassword'] = false;
// $cfg['Servers'][1]['LoginCookieRecall'] = true; // Enable server dropdown

// $cfg['AllowArbitraryServer'] = true;

$cfg['UploadDir'] = '';
$cfg['SaveDir'] = '';

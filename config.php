<?php

return [
    // Database: create it in the InfinityFree control panel (MySQL Databases) and copy these values
    'db_host' => 'sql305.infinityfree.com',
    'db_name' => 'if0_43352851_association',
    'db_user' => 'if0_43352851',
    'db_pass' => '58nofUUlufTR',

    // Public address of the site, no trailing slash. Used in password reset links.
    'site_url' => 'https://association-website.infinityfree.me',

    // Outgoing email (password resets). InfinityFree blocks PHP mail(), so use an SMTP account.
    // Gmail example: smtp.gmail.com, port 587, 'tls', your Gmail address, and an App Password.
    'smtp_host'   => 'smtp.gmail.com',
    'smtp_port'   => 587,
    'smtp_secure' => 'tls',
    'smtp_user'   => '',
    'smtp_pass'   => '',
    'mail_from'   => '',   // for Gmail this must be the same address as smtp_user
];
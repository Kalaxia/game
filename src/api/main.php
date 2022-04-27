<?php

// check le serveur s
// recup la clé
// uncrpyt a
// ajout dans le add

use App\Classes\Worker\API;

$request = $this->getContainer()->get('app.request');

$query = API::unParse($_SERVER['REQUEST_URI']);
$query = explode('/', $query);

foreach ($query as $q) {
    $args = explode('-', $q);

    if (2 == count($args)) {
        $request->query->add($args[0], $args[1]);
    }
}

// réglage de l'encodage
header('Content-type: text/html; charset=utf-8');

if ('dev' === $this->getContainer()->getParameter('environment') || $request->query->has('password')) {
    switch ($request->query->get('a')) {
        // case 'ban': 				include API . 'apis/ban.php'; break;

        default:
        echo serialize([
            'statement' => 'error',
            'message' => 'API non reconnue par le système',
        ]);
        break;
    }
} else {
    echo serialize([
        'statement' => 'error',
        'message' => 'Accès refusé',
    ]);
}

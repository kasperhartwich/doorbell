<?php
require_once __DIR__ . '/../bootstrap.php';

$klein = new \Klein\Klein(null, $app);

$klein->respond('/', function ($request, $response, $service, $app) {
    return $response->redirect('/rings', $code = 302);
});

$klein->respond('/rings',function ($request, $response, $service, $app) {
    $page = $request->page ?: 1;
	$rings = $app->db->rings->select()->orderBy('ringed_at', 'DESC')->paginate($page);

    return $service->render('../views/list.php', array('rings' => $rings, 'pages' => (int)$rings->totalPages(), 'page' => (int)$rings->currentPage()));
});

$klein->respond('/ok', function ($request, $response, $service, $app) {
    return $response->code(200)->chunk('ok');
});

$klein->dispatch();
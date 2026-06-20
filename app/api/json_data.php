<?php
http_response_code(410);
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
echo json_encode(array(
    'error' => 'This endpoint has been disabled.'
));
exit;
?>

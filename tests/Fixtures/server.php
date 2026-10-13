<?php

// Run by tests/Integration/RaceTest.php: answers each request after its own ?delay, without blocking the others.

$server = stream_socket_server('tcp://127.0.0.1:0');

echo stream_socket_get_name($server, false).PHP_EOL;

$pending = [];

while (true) {
    $read = [$server, STDIN];
    $write = $except = null;

    stream_select($read, $write, $except, 0, 10_000);

    if (in_array(STDIN, $read, true) && fread(STDIN, 1) === '') {
        exit;
    }

    if (in_array($server, $read, true)) {
        $connection = stream_socket_accept($server);

        parse_str((string) parse_url(explode(' ', (string) fgets($connection))[1] ?? '', PHP_URL_QUERY), $query);

        $pending[] = [$connection, microtime(true) + (float) ($query['delay'] ?? 0), $query];
    }

    foreach ($pending as $key => [$connection, $due, $query]) {
        if (microtime(true) < $due) {
            continue;
        }

        $body = json_encode(['provider' => $query['provider'] ?? null]);
        $status = (int) ($query['status'] ?? 200);

        @fwrite($connection, "HTTP/1.1 {$status} Status\r\nContent-Type: application/json\r\nContent-Length: ".strlen($body)."\r\nConnection: close\r\n\r\n{$body}");
        fclose($connection);

        unset($pending[$key]);
    }
}

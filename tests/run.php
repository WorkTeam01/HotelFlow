<?php

/** Ejecuta cada tests/*Test.php en su propio proceso; sale con 1 si alguno falla. */

$fallos = 0;
foreach (glob(__DIR__ . '/*Test.php') as $archivo) {
    echo '== ' . basename($archivo) . "\n";
    passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($archivo), $codigo);
    $fallos += $codigo === 0 ? 0 : 1;
}
echo $fallos === 0 ? "\nTodos los archivos OK\n" : "\n$fallos archivo(s) con fallos\n";
exit($fallos === 0 ? 0 : 1);

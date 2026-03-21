<?php
// gen_password_cli.php
// Usage:
//   php gen_password_cli.php "NewPass"          # hash only
//   php gen_password_cli.php admin "NewPass"    # SQL UPDATE line

if (PHP_SAPI !== 'cli') { fwrite(STDERR, "CLI only\n"); exit(1); }
$args = $argv; array_shift($args);

if (count($args) === 1) {
  [$pw] = $args;
  echo password_hash($pw, PASSWORD_BCRYPT), PHP_EOL;
  exit(0);
}

if (count($args) === 2) {
  [$user, $pw] = $args;
  $hash = password_hash($pw, PASSWORD_BCRYPT);

  // Default table; try to read DB_PREFIX if available
  $table = 'nflp_users';
  $cfg = dirname(__DIR__) . '/includes/config.php';
  if (is_file($cfg)) { require $cfg; if (defined('DB_PREFIX')) $table = DB_PREFIX.'users'; }

  $u = str_replace("'", "\\'", $user);
  $h = str_replace("'", "\\'", $hash);
  echo "UPDATE `{$table}` SET `password`='{$h}', `salt`='' WHERE `userName`='{$u}';", PHP_EOL;
  exit(0);
}

fwrite(STDERR, "Usage:\n  php {$argv[0]} \"NewPass\"  # hash only\n  php {$argv[0]} USER \"NewPass\"  # SQL UPDATE line\n");
exit(1);


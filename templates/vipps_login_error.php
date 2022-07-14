<?php

/**
 * Do not allow to frame simpleSAMLphp pages from another location.
 * This prevents clickjacking attacks in modern browsers.
 *
 * If you don't want any framing at all you can even change this to
 * 'DENY', or comment it out if you actually want to allow foreign
 * sites to put simpleSAMLphp in a frame. The latter is however
 * probably not a good security practice.
 */
header('X-Frame-Options: SAMEORIGIN');

?>
<!DOCTYPE html>
<html lang="nb">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0" />
  <meta name="robots" content="noindex, nofollow" />
  <title>Foreningen Blindern Studenterhjem</title>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">
  <link rel="icon" type="image/icon" href="/<?php echo $this->data['baseurlpath']; ?>resources/icons/favicon.ico" />
</head>

<body>
  <div class="navbar navbar-default">
    <div class="container">
      <div class="navbar-header">
        <a class="navbar-brand" href="https://foreningenbs.no/">
          Foreningen Blindern Studenterhjem
        </a>
      </div>
      <p class="navbar-text navbar-right">Pålogging til fellestjenester</p>
    </div>
  </div>

  <div class="container">
    <div class="page-header">
      <h1>Pålogging med Vipps</h1>
    </div>

    <?php





    $email = htmlspecialchars($this->data['email']);

     if (count($this->data['usernames']) == 0) {
        ?>
        <div class="alert alert-danger">
            <p>Vi klarte ikke å finne en bruker registrert med adressen <b><?php echo $email; ?></b> i vårt system.</p>
            <p>Du må ha registrert en foreningsbruker med den samme e-postadressen for å kunne logge inn via Vipps.</p>
        </div>
        <p><a class="btn btn-success" href="https://foreningenbs.no/intern/register">Opprett bruker</a></p>
        <?php
    } else {
        ?>
        <div class="alert alert-danger">
          <p>Det finnes flere brukere med e-postadressen <b><php echo $email; ?></b>, og vi kan derfor ikke logge deg inn.</p>
        </div>
        <?php
    }
    ?>

  </div>
</body>
</html>

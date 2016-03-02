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
  <title>UKA på Blindern - pålogging Google Apps</title>
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
      <h1>Pålogging til Google Apps for UKA</h1>
    </div>

    <?php
    $realname = htmlspecialchars($this->data['attributes']['realname'][0]);
    $username = htmlspecialchars($this->data['attributes']['username'][0]);
    echo '
    <p>Du er innlogget som '.$realname.' ('.$username.').</p>';

    if (count($this->data['usernames']) == 0) {
        ?>
        <div class="alert alert-danger">
            <p>Du har dessverre ingen rettigheter hos UKA og kan ikke logge på noen kontoer der.</p>
            <p>Hvis dette er feil kontakt IKT-ansvarlig på <a href="mailto:admin@blindernuka.no">admin@blindernuka.no</a>.</p>
        </div>
        <?php
    } else {
        ?>

        <p>Velg UKA-bruker du ønsker å logge inn på:</p>

        <div class="form-group">
            <form action="<?php echo htmlspecialchars($this->data['formAction']); ?>" method="post">
                <?php
                foreach ($this->data['formData'] as $key => $value) {
                    echo '
                <input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">';
                }

                foreach ($this->data['usernames'] as $username) {
                    echo '
                <input class="btn btn-default" type="submit" name="username" value="' . htmlspecialchars($username) . '">';
                }
                ?>
            </form>
        </div>

        <p>Hvis du skulle hatt tilgang til flere brukere, kontakt IKT-ansvarlig på <a href="mailto:admin@blindernuka.no">admin@blindernuka.no</a>.</p>

        <?php
    }
    ?>

    <p><a href="https://foreningenbs.no/simplesaml/saml2/idp/SingleLogoutService.php?ReturnTo=https%3A%2F%2Fforeningenbs.no">Logg ut</a></p>

  </div>
</body>
</html>

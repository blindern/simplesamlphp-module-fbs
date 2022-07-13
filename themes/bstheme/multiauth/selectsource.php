<?php

$this->data['header'] = $this->t('{multiauth:multiauth:select_source_header}');

/*if (strlen($this->data['username']) > 0) {
  $this->data['autofocus'] = 'password';
} else {
  $this->data['autofocus'] = 'username';
}*/

/**
 * Support the htmlinject hook, which allows modules to change header, pre and post body on all pages.
 */
$this->data['htmlinject'] = array(
  'htmlContentPre' => array(),
  'htmlContentPost' => array(),
  'htmlContentHead' => array(),
);

if (array_key_exists('pageid', $this->data)) {
  $hookinfo = array(
    'pre' => &$this->data['htmlinject']['htmlContentPre'],
    'post' => &$this->data['htmlinject']['htmlContentPost'],
    'head' => &$this->data['htmlinject']['htmlContentHead'],
    'page' => $this->data['pageid']
  );

  \SimpleSAML\Module::callHooks('htmlinject', $hookinfo);
}
// - o - o - o - o - o - o - o - o - o - o - o - o -

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



$title = array_key_exists('header', $this->data) ? $this->data['header'] : 'simpleSAMLphp';

$htmlHead = '';
if(!empty($this->data['htmlinject']['htmlContentHead'])) {
  foreach($this->data['htmlinject']['htmlContentHead'] AS $c) {
    $htmlHead .= $c;
  }
}

if(array_key_exists('head', $this->data)) {
  $htmlHead .= '<!-- head -->' . $this->data['head'] . '<!-- /head -->';
}

$htmlContentPre = '';
if(!empty($this->data['htmlinject']['htmlContentPre'])) {
  foreach($this->data['htmlinject']['htmlContentPre'] AS $c) {
    $htmlContentPre .= $c;
  }
}

$htmlContentPost = '';
if(!empty($this->data['htmlinject']['htmlContentPost'])) {
  foreach($this->data['htmlinject']['htmlContentPost'] AS $c) {
    $htmlContentPost .= $c;
  }
}


?>
<!DOCTYPE html>
<html lang="nb">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0" />
  <meta name="robots" content="noindex, nofollow" />
  <meta name="prerender-status-code" content="401" />
  <title><?php echo $title; ?></title>

  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">
  <link rel="icon" type="image/icon" href="/<?php echo $this->data['baseurlpath']; ?>resources/icons/favicon.ico" />

  <?php echo $htmlHead; ?>

  <script type="text/javascript">
    window.addEventListener('DOMContentLoaded', function () {
      document.getElementById('username').focus()
    })
  </script>
</head>

<body>
  <div class="navbar navbar-default">
    <div class="container">
      <div class="navbar-header">
        <a class="navbar-brand" href="https://foreningenbs.no/">
          Foreningen Blindern Studenterhjem
        </a>
      </div>
      <p class="navbar-text navbar-right"><?php echo $this->t('{fbs:login:auth_text}'); ?></p>
    </div>
  </div>

  <div class="container">
    <?php echo $htmlContentPre; ?>

    <div class="page-header">
      <h1><?php echo $this->t('{fbs:login:user_pass_header}'); ?></h1>
    </div>
    <p class="logintext"><?php echo $this->t('{fbs:login:user_pass_text}'); ?></p>

    <div class="row">
      <div class="col-md-6">

        <form action="<?php echo htmlentities($_SERVER['PHP_SELF']); ?>" method="get">
          <input type="hidden" name="AuthState" value="<?php echo htmlspecialchars($this->data['authstate']); ?>" />

          <?php
          foreach ($this->data['sources'] as $source) {

            //if ($source['source'] === $this->data['preferred']) {
            //$autofocus = ' autofocus="autofocus"';
            $name = 'src-' . base64_encode($source['source']);

            ?>

            <p>
              <button type="submit" class="btn btn-primary" name="<?php echo htmlspecialchars($name); ?>" value="1">
                <?php echo htmlspecialchars($source['text']); ?>
              </button>
            </p>

            <?php
          }
          ?>

          <p>
            <a class="btn btn-success" href="https://foreningenbs.no/intern/register"><?php echo $this->t('{fbs:login:create_user}'); ?></a>
          </p>

        </form>

      </div>
    </div>

    <p>
        <?php echo $this->t('{fbs:login:help_text}'); ?>
    </p>

    <p>
      Foreningen Blindern Studenterhjem - org.nr <a href="https://w2.brreg.no/enhet/sok/detalj.jsp?orgnr=982118387">982 118 387</a>
    </p>

    <?php

    echo $htmlContentPost;

    ?>

  </div>
</body>
</html>

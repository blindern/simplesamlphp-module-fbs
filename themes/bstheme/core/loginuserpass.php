<?php

$this->data['header'] = $this->t('{fbs:login:user_pass_header}');

if (strlen($this->data['username']) > 0) {
  $this->data['autofocus'] = 'password';
} else {
  $this->data['autofocus'] = 'username';
}

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

    <?php
    if ($this->data['errorcode'] !== NULL) {
      ?>
      <div class="alert alert-danger">
        <p><b><?php echo htmlspecialchars($this->t('{errors:title_' . $this->data['errorcode'] . '}', $this->data['errorparams'])); ?></b></p>
        <p><?php echo htmlspecialchars($this->t('{errors:descr_' . $this->data['errorcode'] . '}', $this->data['errorparams'])); ?></p>
      </div>
      <?php
    }
    ?>

    <div class="page-header">
      <h1><?php echo $this->t('{fbs:login:user_pass_header}'); ?></h1>
    </div>
    <p class="logintext"><?php echo $this->t('{fbs:login:user_pass_text}'); ?></p>

    <div class="row">
      <div class="col-md-6">
        <form class="form-horizontal" action="?" method="post">
          <?php
          foreach ($this->data['stateparams'] as $name => $value) {
            echo '<input type="hidden" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars($value) . '" />';
          }
          ?>

          <div class="form-group">
            <label for="form_username" class="col-lg-4 control-label"><?php echo $this->t('{fbs:login:username}'); ?></label>
            <div class="col-lg-8">
              <input type="text" class="form-control" name="username" id="username" value="<?php echo htmlspecialchars($this->data['username']); ?>">
            </div>
          </div>

          <div class="form-group">
            <label for="form_password" class="col-lg-4 control-label"><?php echo $this->t('{fbs:login:password}'); ?></label>
            <div class="col-lg-8">
              <input type="password" class="form-control" name="password">
            </div>
          </div>

          <?php if ($this->data['rememberUsernameEnabled']): ?>
            <div class="form-group">
              <div class="col-lg-offset-4 col-lg-8">
                <div class="checkbox">
                  <label>
                    <input name="remember_username" type="checkbox" value="Yes"<?php $this->data['rememberUsernameChecked'] ? ' checked' : ''; ?>> <?php echo $this->t('{login:remember_username}'); ?>
                  </label>
                </div>
              </div>
            </div>
          <?php endif; ?>

          <?php if ($this->data['rememberMeEnabled']): ?>
            <div class="form-group">
              <div class="col-lg-offset-4 col-lg-8">
                <div class="checkbox">
                  <label>
                    <input name="remember_me" type="checkbox" value="Yes"<?php $this->data['rememberMeChecked'] ? 'checked' : ''; ?>> <?php echo $this->t('{fbs:login:remember_me}'); ?>
                  </label>
                </div>
              </div>
            </div>
          <?php endif; ?>

          <div class="form-group">
            <div class="col-lg-offset-4 col-lg-8">
              <input type="submit" class="btn btn-default" value="<?php echo $this->t('{login:login_button}'); ?>">
              <a href="https://foreningenbs.no/intern/register" style="margin-left: 10px"><?php echo $this->t('{fbs:login:create_user}'); ?></a>
            </div>
          </div>
        </form>
      </div>
    </div>


    <?php

    /*
    if (array_key_exists('organizations', $this->data)) {
      ?>
      <tr>
        <td style="padding: .3em;"><?php echo $this->t('{login:organization}'); ?></td>
        <td>
          <select name="organization" tabindex="3">
            <?php
            if (array_key_exists('selectedOrg', $this->data)) {
              $selectedOrg = $this->data['selectedOrg'];
            } else {
              $selectedOrg = NULL;
            }

            foreach ($this->data['organizations'] as $orgId => $orgDesc) {
              if (is_array($orgDesc)) {
                $orgDesc = $this->t($orgDesc);
              }

              if ($orgId === $selectedOrg) {
                $selected = 'selected="selected" ';
              } else {
                $selected = '';
              }

              echo '<option ' . $selected . 'value="' . htmlspecialchars($orgId) . '">' . htmlspecialchars($orgDesc) . '</option>';
            }
            ?>
          </select>
        </td>
      </tr>
      <?php
    }
    */
    ?>

    <p>
        <?php echo $this->t('{fbs:login:help_text}'); ?>
    </p>

    <p>
      Foreningen Blindern Studenterhjem - org.nr <a href="https://w2.brreg.no/enhet/sok/detalj.jsp?orgnr=982118387">982 118 387</a>
    </p>

    <?php

    /*
    echo('<h2 class="logintext">' . $this->t('{login:help_header}') . '</h2>');
    echo('<p class="logintext">' . $this->t('{login:help_text}') . '</p>');
    */

    echo $htmlContentPost;

    ?>

  </div>
</body>
</html>

<?php

Class Plasso {
  var $memberData;
  var $plassoToken;
  const plassoUrl = 'https://api.plasso.com';
  const cookieName = '_plasso_flexkit';

  function __construct($plassoToken, $runProtect = true) {
    $this->plassoToken = $plassoToken;
    if ($plassoToken === 'logout') {
      $this->authFail();
      $this->logout();
      return;
    }
    $this->authenticate();
    if ($runProtect) {
      $this->protect();
    }
  }

  function apiQuery($token) {
    return '{
      member(token: "' . $token . '") {
        name,
        email,
        billingInfo {
          street,
          city,
          state,
          zip,
          country
        },
        connectedAccounts {
          id,
          name
        },
        dataFields {
          id,
          value
        },
        id,
        metadata,
        payments {
          id,
          amount,
          createdAt,
          createdAtReadable
        },
        postNotifications,
        shippingInfo {
          name,
          address,
          city,
          state,
          zip,
          country
        },
        sources {
          createdAt,
          id,
          brand,
          last4,
          type
        },
        space {
          id,
          name,
          logoutUrl
        },
        status,
        subscriptions {
          id,
          status,
          createdAt,
          createdAtReadable,
          plan {
            id,
            name
          }
        }
      }
    }';
  }

  function authenticate() {
    if (!isset($this->plassoToken) && isset($_COOKIE[self::cookieName]) && !empty($_COOKIE[self::cookieName])) {
      $cookieJson = json_decode($_COOKIE[self::cookieName], true);
      if (isset($cookieJson['token']) && !empty($cookieJson['token'])) {
        $this->plassoToken = $cookieJson['token'];
      }
    }
    if (empty($this->plassoToken)) {
      $this->authFail();
      return;
    }
    $query = rawurlencode(preg_replace('/\s+/', '', $this->apiQuery($this->plassoToken)));
    $results = file_get_contents(self::plassoUrl . '/?query=' . $query);
    if (!$results){
      $this->authError();
      return;
    } else {
      $json = json_decode($results, true);
      if (isset($json['errors']) && count($json['errors']) > 0) {
        $this->authFail();
        return;
      }
      $this->memberData = $json['data'];
      $cookieValue = json_encode(array('token' => $this->plassoToken, 'logout_url' => $json['data']['member']['space']['logout_url']));
      setcookie(self::cookieName, $cookieValue, time() + 3600, '/', $_SERVER['SERVER_NAME'], false, true);
      $_COOKIE[self::cookieName] = $cookieValue;
    }
  }

  function logout() {
    $logoutUrl = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
    if (isset($_COOKIE[self::cookieName]) && !empty($_COOKIE[self::cookieName])) {
      $cookieJson = json_decode($_COOKIE[self::cookieName], true);
      if (isset($cookieJson['logout_url']) && !empty($cookieJson['logout_url'])) {
        $logoutUrl = $cookieJson['logout_url'];
      }
    }
    echo '<html><head><meta http-equiv="refresh" content="0; URL=' . $logoutUrl . '" /></head><body></body></html>';
    exit;
  }

  function authFail() {
    unset($_COOKIE[self::cookieName]);
    setcookie(self::cookieName, '', time() - 3600, '/', $_SERVER['SERVER_NAME'], false, true);
    $this->plassoToken = 'logout';
  }

  function authError() {
    $this->plassoToken = 'error';
  }

  function errorPage() {
    header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found', true, 404);
    exit;
  }

  function protect() {
    if (isset($this->plassoToken) && $this->plassoToken === 'logout') {
      $this->logout();
    } else if ($this->plassoToken === 'error') {
      $this->errorPage();
    }
  }
}

// To initalize, uncomment the next line:
// $Plasso = new Plasso((isset($_GET['_logout'])) ? 'logout' : (isset($_GET['_plasso_token']) ? $_GET['_plasso_token'] : NULL));
// Access the Plasso Member Data with: $Plasso->memberData

?>

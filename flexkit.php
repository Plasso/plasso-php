<?php
namespace Plasso;

function _send_request($method, $url, $data) {
  $url = "http://plassy.com$url";
  $options = array(
      'http' => array(
          'header'  => "Content-type: application/json\r\n",
          'method'  => $method,
          'content' => json_encode($data)
      )
  );
  $context = stream_context_create($options);
  $result = @file_get_contents($url, false, $context);
  if ($result === FALSE) {
    $error = error_get_last();
    throw new \Exception($error['message']);
  }
  return json_decode($result);
}

Class Flexkit {
  var $public_key;
  var $token;
  const GRAPHQL_GET_DATA = <<<GQL
query getMember(\$token: String) {
  member(token: \$token) {
    id,
    name,
    email,
    ccType,
    ccLast4,
    shippingInfo {
      name
      address
      city
      state
      zip
      country
    },
    dataFields {
      id,
      value
    },
    plan {
      alias
    }
  }
}
GQL;

  private function __construct($public_key, $token) {
    $this->public_key = $public_key;
    $this->token = $token;
  }

  public static function log_in($request) {
    $response = _send_request("POST", "/api/service/login", $request);

    return new Flexkit($request['public_key'], $response->token);
  }

  public static function create_payment($request) {
    _send_request("POST", "/api/payments", $request);
  }

  public static function create_subscription($request) {
    $request['subscription_for'] = "space";

    $response = _send_request("POST", "/api/subscriptions", $request);

    return new Flexkit($request['public_key'], $response->token);
  }
  
  public function get_data() {
    $response = _send_request("POST", "/graphql", array(
      "query" => self::GRAPHQL_GET_DATA,
      "variables" => array(
        "token" => $this->token
      )
    ));
    $member_data = array(
      "credit_card_last4" => $response->data->member->ccLast4,
      "credit_card_type" => $response->data->member->ccType,
      "email" => $response->data->member->email,
      "id" => $response->data->member->id,
      "name" => $response->data->member->name,
      "plan" => $response->data->member->plan->alias
    );

    if ($response->data->member->shippingInfo) {
      $member_data['shipping_name'] = $response->data->member->shippingInfo->name;
      $member_data['shipping_address'] = $response->data->member->shippingInfo->address;
      $member_data['shipping_city'] = $response->data->member->shippingInfo->city;
      $member_data['shipping_state'] = $response->data->member->shippingInfo->state;
      $member_data['shipping_zip'] = $response->data->member->shippingInfo->zip;
      $member_data['shipping_country'] = $response->data->member->shippingInfo->country;
    }
    if ($response->data->member->dataFields) {
      $member_data['data_fields'] = $response->data->member->dataFields;
    }

    return $member_data;
  }

  public function log_out() {
    _send_request("POST", "/api/service/logout", array("token" => $this->token));
  }

  public function update_settings($request) {
    $request['token'] = $this->token;
    _send_request("POST", "/api/services/user?action=settings", $request);
  }

  public function update_credit_card($request) {
    $request['token'] = $this->token;
    _send_request("POST", "/api/services/user?action=cc", $request);
  }

  public function delete() {
    _send_request("DELETE", "/api/service/user", array("token" => $this->token));
  }
}

?>

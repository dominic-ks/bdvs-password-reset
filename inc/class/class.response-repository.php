<?php

class Response_Repository {
  public static function handle( string $status , string $message ) {
    return array(
      'data' => array(
        'status' => $status,
      ),
      'message' => $message,
    );
  }
}

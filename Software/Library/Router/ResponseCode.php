<?php

namespace Grepodata\Library\Router;

// Generic error
define('GD_ERROR_0000', 'Undefined error code');
define('GD_ERROR_1000', 'Unable to handle request');
define('GD_ERROR_1010', 'Bad request. Invalid or missing parameters');
define('GD_ERROR_1200', 'Unable to process request');

// Model not found
define('GD_ERROR_2000', 'Model not found for these parameters.');
define('GD_ERROR_2010', 'Unable to find a user with this id.');
define('GD_ERROR_2020', 'Unable to find an index with this key.');
define('GD_ERROR_2030', 'Unable to find an owner with this id and key.');
define('GD_ERROR_2040', 'Unable to find an alliance with this id.');

// Authentication
define('GD_ERROR_3000', 'Unable to handle authentication request');
define('GD_ERROR_3001', 'Invalid credentials');
define('GD_ERROR_3002', 'Invalid captcha key');
define('GD_ERROR_3003', 'Invalid access token');
define('GD_ERROR_3004', 'Unknown email address');
define('GD_ERROR_3005', 'Invalid password');
define('GD_ERROR_3006', 'Invalid account token');
define('GD_ERROR_3007', 'Unknown username address');
define('GD_ERROR_3008', 'Invalid invite link');
define('GD_ERROR_3009', 'Expired invite link');
define('GD_ERROR_3010', 'Email address is still unconfirmed. Confirm your email address to continue');
define('GD_ERROR_3011', 'Invalid email confirmation token.');
define('GD_ERROR_3030', 'This email address is already in use');
define('GD_ERROR_3031', 'Password is not strong enough. Your password should be at least 8 characters');
define('GD_ERROR_3032', 'This username is already in use');
define('GD_ERROR_3033', 'Username is too short');
define('GD_ERROR_3034', 'Username is too long');
define('GD_ERROR_3035', 'Invalid email address. This address does not comply with naming standards and can not be processed.');
define('GD_ERROR_3040', 'Unlinked script token');
define('GD_ERROR_3041', 'Invalid script token');
define('GD_ERROR_3042', 'Expired script token');
define('GD_ERROR_3043', 'Invalid client');

// Profile
define('GD_ERROR_4000', 'Unable to handle profile request');
define('GD_ERROR_4100', 'Player not found for this id');
define('GD_ERROR_4200', 'Unable to delete account link');

// Discord
define('GD_ERROR_5000', 'Unable to handle discord request');
define('GD_ERROR_5001', 'This guild has not set a default index key required for this request');
define('GD_ERROR_5002', 'This guild has not set a default server required for this request');
define('GD_ERROR_5003', 'Report not found for these parameters');
define('GD_ERROR_5004', 'Server was not able to build this image (internal error)');

// Search
define('GD_ERROR_6000', 'Unable to handle search request');
define('GD_ERROR_6100', 'No players found for these parameters');
define('GD_ERROR_6200', 'No alliances found for these parameters');
define('GD_ERROR_6300', 'No towns found for these parameters');
define('GD_ERROR_6400', 'Enter at least 4 characters to search in users.');
define('GD_ERROR_6401', 'No user found for these parameters.');

// Indexer
define('GD_ERROR_7000', 'Unable to handle indexer request');
define('GD_ERROR_7100', 'No verified players found for this user.');
define('GD_ERROR_7101', 'No index found for this key.');
define('GD_ERROR_7201', 'User is not part of any teams on this world.');
define('GD_ERROR_7500', 'Unauthorized. You do not have access to this index.');
define('GD_ERROR_7501', 'Unauthorized. You are not an owner on this index.');
define('GD_ERROR_7502', 'Unauthorized. You are not an admin on this index.');
define('GD_ERROR_7503', 'Unauthorized. You are not allowed to write to this index.');
define('GD_ERROR_7504', 'Unauthorized. You are not allowed to read this index.');
define('GD_ERROR_7520', 'You can not edit your own rights on this index.');
define('GD_ERROR_7530', "Invalid user role: role must be one of ['read', 'write', 'admin', 'owner']");
define('GD_ERROR_7531', "Invalid owner status: is_hidden must be one of [true, false]");
define('GD_ERROR_7532', "Invalid number of days: num_days must be >= 0 and <= 365");
define('GD_ERROR_7533', "Invalid owner: alliance is already on owner list");
define('GD_ERROR_7534', "Invalid option: can_join_with_v1_key must be true or false");
define('GD_ERROR_7540', "Unauthorized. Only an index owner can change the rights of an index admin/owner.");
define('GD_ERROR_7560', "Unable to update index owners.");
define('GD_ERROR_7570', "User is already a member of this index.");
define('GD_ERROR_7601', "V1 key joining is disabled by owner.");
define('GD_ERROR_7602', "Not a V1 index.");
define('GD_ERROR_7610', "Owner can not leave the index if there are still other members and no other owners.");

// === Success codes
define('GD_SUCCESS_1000', 'Request processed successfully');
define('GD_SUCCESS_1100', 'Renewed existing access token');
define('GD_SUCCESS_1101', 'Renewed access token for valid refresh token');
define('GD_SUCCESS_1102', 'Access token is valid');
define('GD_SUCCESS_1110', 'User login successful');
define('GD_SUCCESS_1111', 'Script login successful');
define('GD_SUCCESS_1120', 'Registration complete, user created');
define('GD_SUCCESS_1130', 'Password changed');
define('GD_SUCCESS_1140', 'Account confirmation requested');
define('GD_SUCCESS_1150', 'Script token created');
define('GD_SUCCESS_1151', 'Script token authenticated');
define('GD_SUCCESS_1160', 'Account removed');
define('GD_SUCCESS_1200', 'Renewed share link');
define('GD_SUCCESS_1201', 'Verified share link');
define('GD_SUCCESS_1250', 'Updated delete days');
define('GD_SUCCESS_1260', 'Updated v1 join status');
define('GD_SUCCESS_1300', 'User index access revoked');
define('GD_SUCCESS_1400', 'V1 index key import successful');
define('GD_SUCCESS_1500', 'User left index successfully');

define('GD_SUCCESS_4000', 'Profile request processed successfully');
define('GD_SUCCESS_4200', 'Account unlinking was successful');


class ResponseCode
{
  /**
   * return a default successful response
   * @param array $aExtraResponseData
   * @param int $HttpCode
   */
  public static function success($aExtraResponseData = array(), $SuccessCode = 1000, $HttpCode = 200)
  {
    if (defined($SuccessCode)) {
      $Message = $SuccessCode;
    } else {
      $Message = constant('GD_SUCCESS_'.$SuccessCode);
      if (is_null($Message)) {
        $Message = GD_SUCCESS_1000;
      }
    }
    $aResponseData = array(
      'success' => true,
      'success_code' => $SuccessCode
    );
    if (!key_exists('message', $aExtraResponseData)) {
      $aResponseData['message'] = $Message;
    }
    if (is_array($aExtraResponseData) && sizeof($aExtraResponseData)) {
      $aResponseData = array_merge($aResponseData, $aExtraResponseData);
    }
    header('Content-Type: application/json', true, $HttpCode);
    die(json_encode($aResponseData, JSON_PRETTY_PRINT));
  }

  /**
   * die with a predefined error code
   * @param int $Code Error code or error constant
   * @param array $aExtraResponseData
   * @param int $HttpCode
   */
  public static function errorCode($Code = 1000, $aExtraResponseData = array(), $HttpCode = 200)
  {
    if (defined($Code)) {
      $Message = $Code;
    } else {
      $Message = constant('GD_ERROR_'.$Code);
      if (is_null($Message)) {
        $Message = GD_ERROR_0000;
      }
    }

    $aResponseData = array(
      'success' => false,
      'error_code' => $Code,
      'message' => $Message,
    );
    if (is_array($aExtraResponseData) && sizeof($aExtraResponseData)) {
      $aResponseData = array_merge($aResponseData, $aExtraResponseData);
    }
    header('Content-Type: application/json', true, $HttpCode);
    die(json_encode($aResponseData, JSON_PRETTY_PRINT));
  }

  /**
   * Die with a custom message and error code of 1000
   * @param $Message string custom message
   * @param int $ErrorCode
   * @param int $HttpCode
   */
  public static function custom($Message, $ErrorCode = 1000, $HttpCode = 200)
  {
    header('Content-Type: application/json', true, $HttpCode);
    die(json_encode(array(
      'success' => false,
      'error_code' => $ErrorCode,
      'message' => $Message,
    ), JSON_PRETTY_PRINT));
  }
}

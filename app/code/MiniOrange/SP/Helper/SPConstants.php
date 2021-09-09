<?php

namespace MiniOrange\SP\Helper;

/** This class lists down constant values used all over our Module. */
class SPConstants
{
	const MODULE_DIR 		= 'MiniOrange_SP';
	const MODULE_TITLE 		= 'SAML 2.0 SP'; 

	//ACL Settings
	const MODULE_BASE 		= '::SP';
	const MODULE_SPSETTINGS = '::sp_settings';
	const MODULE_IDPSETTINGS= '::idp_settings';
	const MODULE_SIGNIN 	= '::signin_settings';
	const MODULE_ATTR  		= '::attr_settings';
	const MODULE_FAQ  		= '::faq_settings';
	const METADATA_DOWNLOAD	= '::metadata';
	const MODULE_ACCOUNT	= '::account_settings';
	const MODULE_SUPPORT	= '::support';
	const MODULE_UPGRADE 	= '::upgrade';

	const MODULE_IMAGES 	= '::images/';
	const MODULE_CERTS 		= '::certs/';
	const MODULE_CSS 		= '::css/';
	const MODULE_JS 		= '::js/';
	const MODULE_GUIDES 	= '::idpsetupguides/';
	const MODULE_METADATA 	= '::metadata/metadata.xml';

	// request option parameter values
	const LOGIN_ADMIN_OPT	= 'loginAdminUser';
	const TEST_CONFIG_OPT 	= 'testConfig';
	const SAML_SSO_FALSE 	= 'saml_sso'; 

	//database keys
	const SESSION_INDEX 	= 'sessionIndex';
	const NAME_ID 			= 'nameId';
	const IDP_NAME 			= 'identityProviderName';
	const X509CERT 			= 'certificate';
	const RESPONSE_SIGNED 	= 'responseSigned';
	const ASSERTION_SIGNED 	= 'assertionSigned';
	const ISSUER 			= 'samlIssuer';
	const DB_FIRSTNAME 		= 'firstname';
	const DB_LASTNAME 		= 'lastname';
	const AUTO_REDIRECT 	= 'autoRedirect';
	const SAML_SSO_URL 		= 'ssourl';
	const SAML_SLO_URL 		= 'logouturl';
	const BINDING_TYPE 		= 'loginBindingType';
	const LOGOUT_BINDING 	= 'logoutBindingType';
	const FORCE_AUTHN 		= 'forceAuthn';
	const SAMLSP_KEY 		= 'customerKey';
	const SAMLSP_EMAIL		= 'email';
	const SAMLSP_PHONE		= 'phone';
	const SAMLSP_CNAME		= 'cname';
	const SAMLSP_FIRSTNAME	= 'customerFirstName';
	const SAMLSP_LASTNAME	= 'customerLastName';
	const SAMLSP_CKL 		= 'ckl';
	const SAMLSP_LK 		= 'lk';
	const BACKDOOR 			= 'backdoor';
	const SHOW_ADMIN_LINK 	= 'showadminlink';
	const SHOW_CUSTOMER_LINK= 'showcustomerlink';
	const REG_STATUS 		= 'registrationStatus';
	const API_KEY 			= 'apiKey';
	const TOKEN 			= 'token';
	const BUTTON_TEXT 		= 'buttonText';
	const OTP_TYPE 			= 'otpType';

	// attribute mapping constants
	const MAP_EMAIL 		= 'amEmail';
	const DEFAULT_MAP_EMAIL = 'email';
	const MAP_USERNAME		= 'amUsername';
	const DEFAULT_MAP_USERN = 'username';
	const MAP_FIRSTNAME 	= 'amFirstName';
	const DEFAULT_MAP_FN 	= 'firstName';
	const MAP_LASTNAME 		= 'amLastName';
	const MAP_DEFAULT_ROLE 	= 'defaultRole';
	const DEFAULT_ROLE 		= 'General';
	const MAP_MAP_BY 		= 'amAccountMatcher';
	Const DEFAULT_MAP_BY 	= 'email';
	const MAP_GROUP 		= 'amGroupName';
	const MAP_DEFAULT_GROUP ='defaultGroup';
	const TEST_RELAYSTATE 	= 'testvalidate';
	const UNLISTED_ROLE 	= 'unlistedRole';
	const CREATEIFNOTMAP 	= 'createUserIfRoleNotMapped';
	const GROUPS_MAPPED 	= 'samlAdminRoleMapping';
	const ROLES_MAPPED 	= 'samlCustomerRoleMapping';
	
	//URLs
	const ISSUER_URL_PATH 	= 'mospsaml/metadata/index';
	const SAML_LOGIN_URL 	= 'mospsaml/actions/sendAuthnRequest';

	//session data
	const USER_LOGOUT_DETAIL= 'userDetails';
	const SEND_RESPONSE 	= 'sendLogoutResponse';
	const LOGOUT_REQUEST_ID = 'logoutRequestId';
	const TXT_ID 			= 'miniorange/samlsp/transactionID';

	//images
	const IMAGE_RIGHT 		= 'right.png';
	const IMAGE_WRONG 		= 'wrong.png';

	//certs
	const SP_KEY 			= 'sp-key.key';
	const ALTERNATE_KEY 	= 'miniorange_sp_priv_key.key'; 
	const PUBLIC_KEY 		= 'sp-certificate.crt';	

	//SAML Constants
	const SAML 	 			= 'SAML';
	const AUTHN_REQUEST 	= 'AuthnRequest';
	const SAML_RESPONSE 	= 'SamlResponse';
	const WS_FED_RESPONSE 	= 'WsFedResponse';
	const HTTP_REDIRECT 	= 'HttpRedirect';
	const LOGOUT_REQUEST 	= 'LogoutRequest';

	//OTP Constants
	const OTP_TYPE_EMAIL	= 'email';
	const OTP_TYPE_PHONE	= 'sms';

	//Registration Status
	const STATUS_VERIFY_LOGIN 	= "MO_VERIFY_CUSTOMER";
	const STATUS_COMPLETE_LOGIN = "MO_VERIFIED";
	const STATUS_VERIFY_EMAIL 	= "MO_OTP_EMAIL_VALIDATE";

	//plugin constants
	const DEFAULT_CUSTOMER_KEY 	= "16555";
	const DEFAULT_API_KEY 		= "fFd2XcvTGDemZvbw1bcUesNJWEqKbbUq";
	const APPLICATION_NAME		= "MAGENTO_SAML_PREMIUM_EXTENSION";
    const HOSTNAME				= "https://login.xecurify.com";
	const AREA_OF_INTEREST 		= 'Magento 2.0 Saml SP Plugin';
    const DB_USER =               'user';
}

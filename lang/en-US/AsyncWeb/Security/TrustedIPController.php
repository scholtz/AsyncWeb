<?php

$L["LOGIN_trusted_email_text"] = "Trusted IP verification: To add IP address '%ip%' to your trusted IP list please insert the code %code% into the Trusted IP verification box. If you are currently on unsecure internet, and you wish to continue, please verify your connection temporarily for 24 hours by using code <b>%code2%</b>.

If you did not log in into the system, you should probably change your password, because this email was generated with the logged in session in the system.

This might be also Session stealing attack. If you did not login into the system and you do not recognize the IP address, please be aware of suspicious activity with your account.

Best regards,

 Admin";
$L["LOGIN_trusted_email_subject"] = "TrustedIPs verification: %ip%";
$L["LOGIN_trusted_not_valid_email"] = "Your account is not associated with a valid email address!";
$L["LOGIN_form_login_title"] = "Please log in to the system!";
$L["LOGIN_form_login_description"] = "System requires authenticated user!";
$L["LOGIN_form_ipverif_h1"] = "Trusted IP verification";
$L["LOGIN_form_ipverif_text"] = "Trusted IP verification is the process to ensure validity of the user. User can log in only from the trusted IP addresses. Verification is done through the email. Please insert the code the system sent you to your <b>email</b> address. If you have not received your email within 10 minutes, please contact help desk. Trusted IP verification service is here to protect against the unauthorised access to the user account, and protects user against the Session stealing attack. <br />You can choose to submit the code for temporary verification (24 hrs) or the code for full time verification.<br />Your current IP address is <b>%ip%</b>.";
$L["LOGIN_form_ipverif_code"] = "Verification code";
$L["LOGIN_form_ipverif_submit"] = "Submit code";
$L["LOGIN_form_ipverif_verified"] = "IP address has been inserted into your trusted IPs list.";
$L["LOGIN_form_ipverif_verifiedtmp"] = "IP address has been inserted into your trusted IPs list for 24 hours.";
$L["LOGIN_form_ipverif_failed"] = "Code you have entered is invalid!";
$L["LOGIN_form_ipverif_resend"] = "Resend";
$L["LOGIN_form_ipverif_email_sent"] = "Email has been sent";
$L["LOGIN_form_ipverif_email_error"] = "Error occured while sending an email";

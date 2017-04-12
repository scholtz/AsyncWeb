<?php
/**
 * Tato trieda validuje vstup
 *
 *
 *@author Ludko <ludkosk@gmail.com>
 *@version 1.0.0.20051120
 *
 * Example: if(!Validate::check("email","aa@gmail.com")) echo "not an email";
 */
namespace AsyncWeb\Text;
class Validate {
    public static function check_input(&$data, $type) {
        return Validate::check($type, $data);
    }
    /**
     * Tato funkcia vráti true, ak je data typu type.
     *
     * mozne typy:
     *  number 		.. cele cislo, skladajuce sa iba z cislic (indexy alebo podobne cisla)
     *  alpha_only 	.. iba pismena
     *  alnum_only 	.. iba cisla a pismena
     *  safe_url   	.. iba cisla, pismena a podtrzitko
     *  email	    .. zisti ci v data je uvedeny email
     *
     * @param string $type digit_only alpha_only alnum_only safe_url email 1 2 3 4 5 tel string
     * @param unknown_type $data
     * @return bool Je validne
     */
    public static function check($type, &$data) {
        $string = $data;
        switch (strtolower($type)) {
            case 'digit_only':
            case 'number':
                $data = str_replace(" ", "", $data);
                $data = str_replace(",", ".", $data);
                if (preg_match('/^\d+$/', $data)) return true;
                if (preg_match('/^\d+\.\d+$/', $data)) return true;
                return false;
                break;
            case 'alpha_only':
                if (preg_match('/^[a-zA-Z]+$/', $data)) return true;
                return false;
                break;
            case 'alpha_space_only':
                if (preg_match("/^[[a-zA-Z] ]+$/", $data)) return true;
                return false;
                break;
            case 'alnum_only':
                if (preg_match('/^\w+$/', $data)) return true;
                return false;
                break;
            case 'safe_url':
                if (preg_match('/^[\w\-]+$/', $data)) return true;
                return false;
                break;
            case 'email':
                if (filter_var($data, FILTER_VALIDATE_EMAIL)) return true;
                //if(preg_match("/^[[a-zA-Z][:digit:]\_\.\-]+\@[[:alpha:][:digit:]\_\.\-]+\.[[:alpha:][:digit:]]+$/",$data)) return true;
                return false;
                break;
            case '':
                return true;
            case '1':
                if (preg_match("\d+", $data)) {
                    return true;
                } else {
                    return false;
                }
                break;;
            case '2':
                if (ereg("[<>\"'!@#\$%^&*;\\\`\]", $data)) {
                    return false;
                } else {
                    return true;
                }
                break;;
            case '3':
                if (ereg("[<>\"']", $data)) {
                    return false;
                } else {
                    return true;
                }
                break;;
            case '4':
                if (ereg("[\"']", $data)) {
                    return false;
                } else {
                    return true;
                }
                break;;
            case '5':
                if (ereg("[<>]", $data)) {
                    return false;
                } else {
                    return true;
                }
                break;;
            case 'tel':
                if (ereg("^[[:digit:] +/][[:digit:] +/]*$", $data)) {
                    return true;
                } else {
                    return false;
                }
                break;;
            case 'string':
                return true;
                break;;
            default:
                return true;
            }
        }
    };
?>
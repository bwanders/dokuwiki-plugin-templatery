<?php
/**
 * DokuWiki Plugin skeleton (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Brend Wanders <b.wanders@utwente.nl>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once DOKU_PLUGIN.'syntax.php';
require_once DOKU_PLUGIN.'templatery/templatery_handler.php';

class syntax_plugin_templatery_include extends DokuWiki_Syntax_Plugin {
    public function __construct() {
        $this->helper =& plugin_load('helper', 'templatery');
    }

    public function getType() {
        return 'substition';
    }

    public function getPType() {
        return 'block';
    }

    public function getSort() {
        return 300;
    }


    public function connectTo($mode) {
        $this->Lexer->addSpecialPattern('\{\{template>[^}]+?}}',$mode,'plugin_templatery_include');
    }

    public function handle($match, $state, $pos, &$handler){
        global $ID;

        preg_match('/\{\{template>([^\}|]+?)(?:\|([^}]+?))?}}/msS',$match,$capture);
        $page = $capture[1];
        $vars = $capture[2];

        $variables = array();
        $vars = explode('|', $vars);
        $j = 0;
        for($i=0;$i<count($vars);$i++) {
            if(trim($vars[$i])=='') continue;
            if(preg_match('/^(.+?)=(.+)$/',$vars[$i],$capture)) {
                $variables[$capture[1]] = $capture[2];
            } else {
                $variables[$j++] = $vars[$i];
            }
        }

        $template = $this->helper->loadTemplate($page, $handler);

        return array($page, $template, $variables);
    }

    public function render($mode, &$R, $data) {
        list($page, $template, $variables) = $data;

        // check for permission
        if (auth_quickaclcheck($template['source']) < AUTH_READ) {
            $template['instructions'] = null;
        }

        if($template['instructions'] != null) {
            // display template
            $handler = new templatery_include_handler($variables);
            $this->helper->applyTemplate($template, $handler, $R);
        } else {
            $R->p_open();
            $R->internalLink($template['source'], '[template \''.$page.'\' not available]');
            $R->p_close();
        }


        return true;
    }
}

class templatery_include_handler implements templatery_handler {
    public function __construct($variables) {
        $this->vars = $variables;
    }

    public function hasField($field) {
        return isset($this->vars[$field]);
    }

    public function getField($mode, &$R, $field, $default=null) {
        return $this->hasField($field) ? $this->vars[$field] : $default;
    }

    public function displayField($mode, &$R, $field, $default=null) {
        if($mode != 'xhtml') return false;

        $value = $this->getField($mode, $R, $field, $default);

        if($value != null) $R->doc .= $R->_xmlEntities($value);

        return true;
    }
}

// vim:ts=4:sw=4:et:

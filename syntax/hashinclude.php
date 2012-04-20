<?php
/**
 * DokuWiki Plugin templatery (Fake header)
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

class syntax_plugin_templatery_hashinclude extends DokuWiki_Syntax_Plugin {
    function __construct() {
        $this->helper =& plugin_load('helper','templatery');
    }

    function getType() {
        // invented new type
        return 'templated';
    }

    function getSort() {
        return 290;
    }

    function getPType() {
        return 'block';
    }

    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('@@#include +.+?@@', $mode, 'plugin_templatery_hashinclude');
    }

    function handle($match, $state, $pos, &$handler) {
        preg_match('/@@#include +([^\|]+?)(?:\|(.+?))?@@/msS',$match,$capture);
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
            // check if we are delegating
            if($this->helper->isDelegating()) {
                // display template
                $handler = new templatery_hashinclude_handler($variables, $this->helper->getDelegate());
                $this->helper->applyTemplate($template, $handler, $R);
                return true;
            }

            // render a preview
            if($mode != 'xhtml') return false;

            $R->doc .= '<p class="templatery-hashinclude"><span>&#8249;#include ';
            $R->internallink($template['source'],$page);
            $R->doc .= '&#8250;</span></p>';
        } else {
            $R->p_open();
            $R->internalLink($template['source'], '[template \''.$page.'\' not available: '.$template['error'].']');
            $R->p_close();
        }
 
        return true;
    }
}

class templatery_hashinclude_handler implements templatery_handler {
    public function __construct($variables, $parent) {
        $this->translation = $variables;
        $this->parent = $parent;
    }

    public function hasField($field) {
        if($this->parent != null && isset($this->translation[$field])) {
            return $this->parent->hasField($this->translation[$field]);
        }

        return false;
    }

    public function getField($mode, &$R, $field, $default=null) {
        if($this->parent != null && isset($this->translation[$field])) {
            return $this->parent->getField($mode, $R, $this->translation[$field], $default);
        }

        return null;
    }

    public function displayField($mode, &$R, $field, $default=null) {
        if($this->parent != null && isset($this->translation[$field])) {
            return $this->parent->displayField($mode, $R, $this->translation[$field], $default);
        }

        return true;
    }
}

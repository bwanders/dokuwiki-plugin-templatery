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

class syntax_plugin_templatery_template extends DokuWiki_Syntax_Plugin {
    public function __construct() {
        $this->helper =& plugin_load('helper', 'templatery');
    }

    public function getType() {
        return 'baseonly';
    }

    public function getPType() {
        return 'block';
    }

    public function getSort() {
        return 300;
    }


    public function connectTo($mode) {
        $this->Lexer->addSpecialPattern('\{\{template>[^}]+?}}',$mode,'plugin_templatery_template');
    }

    public function handle($match, $state, $pos, &$handler){
        global $ID;

        preg_match('/\{\{template>([^\}|]+?)(?:\|([^}]+?))?}}/msS',$match,$capture);
        $id = $capture[1];
        $vars = $capture[2];

        $variables = array();
        $vars = explode('|', $vars);
        $j = 0;
        for($i=0;$i<count($vars);$i++) {
            if(trim($vars[$i])=='') continue;
            if(preg_match('/^(.+?)=(.*)$/',$vars[$i],$capture)) {
                $variables[$capture[1]] = $capture[2];
            } else {
                $variables[$j++] = $vars[$i];
            }
        }

        return array($id, $variables);
    }

    public function render($mode, &$R, $data) {
        list($id, $variables) = $data;

        list($page, $hash) = $this->helper->resolveTemplate($id, $exists);

        // add reference for backlinks
        if($mode == 'metadata') {
            $R->meta['relation']['references'][$page] = $exists;
        }

        // check for permission
        if (auth_quickaclcheck($page) < AUTH_READ) {
            $error = 'template_unavailable';
        }

        // load the template
        $template = $this->helper->loadTemplate($page, $hash);

        if($template == null) {
            $error = 'template_nonexistant';
        }

        // render errors as messages
        if(isset($error)) {
            if($mode == 'xhtml') {
                msg(sprintf($this->getLang($error),$id),-1);
                $R->p_open();
                $R->doc .= '<span class="templatery-error">';
                $R->internallink($page,sprintf($this->getLang($error),$id));
                $R->doc .= '</span>';
                $R->p_close();
            }
        } else {
            // display template
            $handler = new templatery_template_handler($variables);
            $this->helper->applyTemplate($template, $handler, $R);
        }

        return true;
    }
}

class templatery_template_handler implements templatery_handler {
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

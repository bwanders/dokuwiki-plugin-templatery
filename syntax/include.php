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

class syntax_plugin_templatery_include extends DokuWiki_Syntax_Plugin {
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
        $this->Lexer->addSpecialPattern('\{\{template>[^}]+?}}', $mode, 'plugin_templatery_include');
    }

    function handle($match, $state, $pos, &$handler) {
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
            $template['error'] = 'template_unavailable';
        }

        // are we 'live', and do we have actual instructions?
        if($this->helper->isDelegating()) {
            // render errors as messages
            if(isset($template['error'])) {
                if($mode == 'xhtml') {
                    msg(sprintf($this->getLang($template['error']),$page),-1);
                    $R->p_open();
                    $R->doc .= '<span class="templatery-error">';
                    $R->internallink($template['source'],sprintf($this->getLang($template['error']),$page));
                    $R->doc .= '</span>';
                    $R->p_close();
                }

                // abort further rendering
                return false;
            }

            // display template
            $handler = new templatery_include_handler($variables, $this->helper->getDelegate());
            $this->helper->applyTemplate($template, $handler, $R);
            return true;
        } else {
            // render a preview
            if($mode == 'xhtml') {
                $R->doc .= '<p class="templatery-include"><span>&#8249;';
                $R->internallink($template['source'],$page);
                if(isset($template['error']) && $template['error'] != 'template_nonexistant') {
                    $R->doc .= ': '. $R->_xmlEntities(sprintf($this->getLang($template['error']),$page));
                }
                $R->doc .= '&#8250;</span></p>';
                return true;
            } elseif($mode == 'metadata') {
                // render internal link to allow cache and backlinking to work for templates
                $R->internallink($template['source'],$page);
                return true;
            }

            return false;
        }

        return false;
    }
}

class templatery_include_handler implements templatery_handler {
    public function __construct($variables, $parent) {
        $this->translation = $variables;
        $this->parent = $parent;
    }

    private function getTranslation($field) {
        if (isset($this->translation[$field])) {
            return $this->translation[$field];
        }

        return $field;
    }

    public function hasField($field) {
        if($this->parent != null) {
            return $this->parent->hasField($this->getTranslation($field));
        }

        return false;
    }

    public function getField($mode, &$R, $field, $default=null) {
        if($this->parent != null) {
            return $this->parent->getField($mode, $R, $this->getTranslation($field), $default);
        }

        return null;
    }

    public function displayField($mode, &$R, $field, $default=null) {
        if($this->parent != null) {
            return $this->parent->displayField($mode, $R, $this->getTranslation($field), $default);
        }

        return true;
    }
}

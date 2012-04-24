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
        $id = $capture[1];
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

        return array($id, $variables);
    }

    public function render($mode, &$R, $data) {
        list($id, $variables) = $data;

        list($page, $hash) = $this->helper->resolveTemplate($id, $exists);

        if($mode == 'metadata') {
            // add reference for backlinks
            $R->meta['relation']['references'][$page] = $exists;

            // add page to list for cache handling
            if(!isset($R->meta['plugin_templatery'])) {
                $R->meta['plugin_templatery'] = array(
                    'all'=>array(),
                    'actual'=>array()
                );
            }
            $R->meta['plugin_templatery']['all'][] = $page;
        }

        // check for permission
        if (auth_quickaclcheck($page) < AUTH_READ) {
            $error = 'template_unavailable';
        }

        // add the page to the list of actual pages if it is readable
        if($mode == 'metadata' && !isset($error)) {
            $R->meta['plugin_templatery']['actual'][] = $page;
        }

        // load the template
        $template = $this->helper->loadTemplate($page, $hash);

        if($template == null) {
            $error = 'template_nonexistant';
        }

        // render errors as messages
        if($this->helper->isDelegating()) {
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
                $handler = new templatery_include_handler($variables, $this->helper->getDelegate());
                $this->helper->applyTemplate($template, $handler, $R);
            }
        } else {
            // render a preview
            if($mode == 'xhtml') {
                $R->doc .= '<p class="templatery-include"><span>&#8249;';
                $R->internallink($page,$id);
                /*
                if(isset($error)) {
                    $R->doc .= ' ('. $R->_xmlEntities(sprintf($this->getLang($error),$id)).') ';
                }
                */
                if(count($variables)) {
                    $R->doc .= '&#187; '.implode(', ',array_map(function($from,$to){return hsc($to).' &#8594; '.hsc($from);},array_keys($variables),$variables));
                }
                $R->doc .= '&#8250;</span></p>';
            }
        }

        return true;
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

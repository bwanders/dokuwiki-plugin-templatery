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

class syntax_plugin_templatery_include extends syntax_plugin_templatery_template {
    function __construct() {
        parent::__construct();
    }

    function getType() {
        // invented new type
        return 'templated';
    }

    function getSort() {
        return 290;
    }

    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('\{\{template>[^}]+?}}', $mode, 'plugin_templatery_include');
    }

    /**
     * Renders the actual template.
     */
    protected function internalRender($mode, &$R, &$template, $id, $page, $hash, &$variables, $error) {
        // render errors as messages
        if($this->helper->isDelegating()) {
            parent::internalRender($mode, $R, $template, $id, $page, $hash, $variables, $error);
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
    }

    /**
     * Instantiates a new handler.
     */
    protected function newHandler($mode, &$R, &$template, $id, $page, $hash, &$variables) {
        return new templatery_include_handler($variables, $this->helper->getDelegate());
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

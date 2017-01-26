<?php
/**
 * DokuWiki Plugin Templatery (Fake header)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Brend Wanders <b.wanders@utwente.nl>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die('Meh.');

/**
 * Handles template inclusion in a template.
 */
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

    public function render($mode, $R, $data) {
        list($id, $variables, $sectioning) = $data;

        list($page, $hash) = $this->helper->resolveTemplate($id, $exists);

        // prepare template for rendering
        $template = $this->helper->prepareTemplate($mode, $R, $page, $hash, $error);
        
        // check that we're not previewing
        if($this->helper->isDelegating()) {
            $handler = new templatery_include_handler($variables, $this->helper->getDelegate());

            $this->helper->renderTemplate($mode, $R, $template, $id, $page, $hash, array(false), $handler, $error);
        } else {
            // render a preview
            if($mode == 'xhtml') {
                $R->doc .= '<p><span class="templatery-include">';
                $R->doc .= '<span>'; $R->internallink($page,$id); $R->doc .= '</span>';
                if(count($variables)) {
                    $R->doc .= ': '.implode(', ',array_map(function($from,$to){return hsc($to).' &#8594; '.hsc($from);},array_keys($variables),$variables));
                }
                $R->doc .= '</span></p>';
            }
        }

        return true;
    }
}

class templatery_include_handler implements templatery_handler {
    public function __construct($variables, $parent) {
        $this->translation = $variables;
		foreach($variables as $key=>$value) {
			$this->translation[strtolower($key)] = $value;
		}
        $this->parent = $parent;
    }

    public function listFields() {
        if($this->parent != null) {
            $base = $this->parent->listFields();
            return array_merge($base, array_keys($this->translation));
        }

        return array();
    }

    private function getTranslation($field) {
		$field = strtolower($field);
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

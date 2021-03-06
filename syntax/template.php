<?php
/**
 * DokuWiki Plugin skeleton (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Brend Wanders <b.wanders@utwente.nl>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die('Meh.');

/**
 * Syntax for template inclusion.
 */
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

    public function handle($match, $state, $pos, Doku_Handler $handler){
        preg_match('/\{\{template>([^\}|]+?)(?:\|([^}]+?))?}}/msS',$match,$capture);
        $id = $capture[1];
        $vars = $capture[2];

        // parse variables
        $variables = array();
        $vars = explode('|', $vars);
        $j = 0;
        for($i=0;$i<count($vars);$i++) {
            if(trim($vars[$i])=='') continue;
            if(preg_match('/^(.+?)=(.*)$/',$vars[$i],$capture)) {
                $variables[$capture[1]] = trim($capture[2]);
            } else {
                $variables[$j++] = trim($vars[$i]);
            }
        }

        // did we include a template into a section?
        $sectioning = $this->helper->getSectioning($handler);

        return array($id, $variables, $sectioning);
    }

    public function render($mode, Doku_Renderer $R, $data) {
        list($id, $variables, $sectioning) = $data;

        list($page, $hash) = $this->helper->resolveTemplate($id, $exists);

        $template = $this->helper->prepareTemplate($mode, $R, $page, $hash, $error);

        $handler = new templatery_template_handler($variables);

        $this->helper->renderTemplate($mode, $R, $template, $id, $page, $hash, $sectioning, $handler, $error);

        return true;
    }
}

class templatery_template_handler implements templatery_handler {
    public function __construct($variables) {
        $this->vars = array();
        foreach($variables as $key=>$value) {
            $this->vars[strtolower($key)] = $value;
        }
    }

    public function listFields() {
        return array_keys($this->vars);
    }

    public function hasField($field) {
        $field = strtolower($field);
        return isset($this->vars[$field]);
    }

    public function getField($mode, &$R, $field, $default=null) {
        $field = strtolower($field);
        return $this->hasField($field) ? $this->vars[$field] : $default;
    }

    public function displayField($mode, &$R, $field, $default=null) {
        if($mode != 'xhtml') return false;

        $field = strtolower($field);

        $value = $this->getField($mode, $R, $field, $default);

        if($value != null) $R->doc .= $R->_xmlEntities($value);

        return true;
    }
}

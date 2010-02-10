<?php
namespace phpx;

class ParseError extends \Exception {}

function class_eval_without_scope($class, $___block___) {
    eval($___block___);
}

class Parser
{
    private $text;
    private $tokens;
    private $curr;
    private $last_annotation;
    private $ix;
    
    private $namespace      = '';
    
    public function parse($text) {
        $this->reset($text);
        return $this->parse_source_file();
    }
    
    public function reset($text) {
        $this->text = $text;
        $this->tokens = token_get_all($this->text);
        $this->last_annotation = null;
        $this->ix = -1;
        $this->advance();
        
    }
    
    public function parse_source_file() {
        $source = new SourceFile;
        while (!$this->eof()) {
            if ($this->at(array(T_ABSTRACT, T_FINAL, T_CLASS))) {
                $source->push($this->parse_class());
            } elseif ($this->at(T_NAMESPACE)) {
                $this->accept();
                $this->s();
                // TODO: finish
            } else {
                $source->push($this->current_text());
                $this->accept();
            }
        }
        return $source;
    }
    
    public function parse_class() {
        
        $class_annotation = $this->last_annotation;
        $this->last_annotation = null;
      
        // abstract class
      
        $abstract   = false;
        $final      = false;
        
        while ($this->at(array(T_ABSTRACT, T_FINAL))) {
            if ($this->at(T_ABSTRACT)) {
                $abstract = true;
            } else {
                $final = true;
            }
            $this->accept();
            $this->s();
        }
        
        // class
      
        $this->accept(T_CLASS);
        $this->s();
        
        $class = new ClassDef($this->parse_ident());
        $class->set_abstract($abstract);
        $class->set_namespace($this->namespace);
        if ($class_annotation) {
            $class->set_annotation($class_annotation);
        }
        $this->s();
        
        // superclass
        
        if ($this->at(T_EXTENDS)) {
            $this->accept();
            $this->s();
            $class->extend($this->parse_ident());
            $this->s();
        }
        
        // interfaces
        
        $req = T_IMPLEMENTS;
        while ($this->at($req)) {
            $this->accept();
            $this->s();
            $class->implement($this->parse_ident());
            $this->s();
            $req = ',';
        }
        
        $this->s();
        $this->accept('{');
        
        // forward declarations: before
        Forward::apply('before', $class);
        
        // const, var, methods, eval, mixin
        
        $this->s();
        while ($this->at_class_part()) {
        
            if ($this->at(T_CONST)) {
                
                $this->accept();
                $this->s();
                $ident = $this->parse_ident();
                $this->s();
                $this->accept('=');
                $this->s();
                $class->define_constant($ident, $this->parse_value());
                $this->s();
                $this->accept(';');
                
            } elseif ($this->at_qualifier()) {
                
                $member_annotation = $this->last_annotation;
                $this->last_annotation = null;

                $access     = 'public';
                $static     = false;
                $abstract   = false;
                $final      = false;

                while ($this->at_qualifier()) {
                    switch ($this->current_token()) {
                        case T_PUBLIC: $access = 'public'; break;
                        case T_PRIVATE: $access = 'private'; break;
                        case T_PROTECTED: $access = 'protected'; break;
                        case T_STATIC: $static = true; break;
                        case T_FINAL: $final = true; break;
                        case T_ABSTRACT: $abstract = true; break;
                    }
                    $this->accept();
                    $this->s();
                }

                if ($this->at(T_VARIABLE)) {
                    
                    $variable = $this->parse_variable($access, $static);
                    if ($member_annotation) {
                        $variable->set_annotation($member_annotation);
                    }
                    
                    $class->add_variable($variable);
                    $this->s();
                    
                    if ($this->at(T_IMPLEMENTS)) {
                        
                        $this->accept();
                        $this->s();
                        $this->write_interface_delegate($class,
                                                        $variable->get_name(),
                                                        $this->parse_absolute_namespaced_ident());
                        $this->s();
                        
                        while ($this->at(',')) {
                            $this->accept();
                            $this->s();
                            $this->write_interface_delegate($class,
                                                            $variable->get_name(),
                                                            $this->parse_absolute_namespaced_ident());
                            $this->s();
                        }
                    
                    } else {
                        
                        while ($this->at(',')) {
                            $this->accept();
                            $this->s();
                            $class->add_variable($this->parse_variable($access, $static));
                            $this->s();
                        }
                    
                    }
                    
                    $this->accept(';');
                
                } elseif ($this->at(T_FUNCTION)) {
                    
                    $this->accept();
                    $this->s();
                    
                    if ($this->at('&')) {
                        $reference = true;
                        $this->accept();
                        $this->s();
                    } else {
                        $reference = false;
                    }
                    
                    if ($this->at(T_CONSTANT_ENCAPSED_STRING)) {
                        
                        if ($access != 'public') $this->error("pattern matched methods must be public");
                        if ($final) $this->error("pattern matched methods cannot be final");
                        if ($abstract) $this->error("pattern matched methods cannot be abstract");
                        if ($static) $this->error("pattern matched methods cannot be static (for now)");
                        if ($reference) $this->error("pattern matched methods cannot return by reference");
                        
                        $pattern = $this->current_text();
                        $this->accept();
                        $this->s();
                        $args = $this->parse_arg_list();
                        $this->s();
                        $body = substr($this->parse_block(), 1, -1);
                        
                        $class->add_pattern(new Literal($pattern), $args, $body);
                        
                    } else {
                        
                        $ident = $this->parse_ident();
                        $this->s();
                        $args = $this->parse_arg_list();
                        $this->s();
                        if ($this->at(';')) {
                            $body = '';
                            $this->accept();
                        } else {
                            $body = substr($this->parse_block(), 1, -1);
                        }

                        $method = new Method($ident);
                        $method->set_access($access);
                        $method->set_static($static);
                        $method->set_final($final);
                        $method->set_abstract($abstract);
                        $method->set_reference_returned($reference);
                        $method->set_arg_list($args);
                        $method->set_body($body);

                        $class->add_method($method);
                        
                        if ($member_annotation) {
                            $method->set_annotation($member_annotation);
                        }
                        
                    }
                    
                }

            } elseif ($this->at(T_EVAL)) {
                
                $this->accept();
                $this->s();
                $code = $this->parse_block();
                class_eval_without_scope($class, $code);
            
            } elseif ($this->at(T_INCLUDE)) {
                
                $this->accept();
                $this->s();
                
                $mixin_method = 'mixin';
                if ($this->at(T_CONST)) {
                    $mixin_method .= '_constants';
                    $this->accept();
                    $this->s();
                } elseif ($this->at(T_STATIC)) {
                    $mixin_method .= '_static';
                    $this->accept();
                    $this->s();
                }
                
                $module = $this->parse_absolute_namespaced_ident();
                $this->s();
                $this->accept(';');
                
                $class->{$mixin_method}($module);
                
            }

            $this->s();
              
        }
        
        $this->s();
        $this->accept('}');
        
        // forward declarations: after
        Forward::apply('after', $class);
        
        return $class;
    }
    
    private function parse_variable($access, $static) {
        if ($this->at(T_VARIABLE)) {
            $var = new Variable(substr($this->current_text(), 1));
            $var->set_access($access);
            $var->set_static($static);
            $this->accept();
            $this->s();
            if ($this->at('=')) {
                $this->accept();
                $this->s();
                $var->set_value($this->parse_value());
            }
            return $var;
        } else {
            // parse error
        }
    }
    
    private function parse_arg_list() {
        $args = $this->consume_balanced_block(array('('), array(')'));
        $args = substr($args, 1);
        $args = substr($args, 0, -1);
        return $args;
    }
    
    private function parse_block() {
        return $this->consume_balanced_block(array('{', T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES), array('}'));
    }
    
    private function parse_ident() {
        $text = $this->current_text();
        $this->accept(T_STRING);
        return $text;
    }
    
    private function parse_namespaced_ident() {
        $ident = '';
        while ($this->at(array(T_NS_SEPARATOR, T_STRING))) {
            $ident .= $this->current_text();
            $this->accept();
        }
        return $ident;
    }
    
    private function parse_absolute_namespaced_ident() {
        $ident = $this->parse_namespaced_ident();
        if ($ident[0] != '\\') $ident = '\\' . $ident;
        return $ident;
    }
    
    // Parses a value and returns it as a Literal
    // Using literals simplifies parsing, especially for arrays.
    // However, we lose the semantics
    // Ideally we'd parse everything into Value instances...
    private function parse_value() {
        switch ($this->current_token()) {
            case T_ARRAY:
                $this->accept();
                $this->s();
                $block = $this->consume_balanced_block(array('('), array(')'));
                $text = 'array' . $block;
                return new Literal($text);
            case T_LNUMBER: // integer
            case T_DNUMBER: // float
            case T_CONSTANT_ENCAPSED_STRING: // string constant without interpolation
            case T_STRING: // covers true, false, null, constant references
                $val = new Literal($this->current_text());
                $this->accept();
                return $val;
            default:
                $this->error("couldn't parse value");
        }
    }
    
    private function eof() {
        return $this->curr === null;
    }
    
    private function at($token) {
        $c = $this->current_token();
        if (is_array($token)) {
            foreach ($token as $t) {
                if ($c == $t) return true;
            }
            return false;
        } else {
            return $c == $token;
        }
    }
    
    private function at_class_part() {
        return $this->at_qualifier()
                || $this->at(T_CONST)
                || $this->at(T_EVAL)
                || $this->at(T_INCLUDE);
    }
    
    private function at_qualifier() {
        return $this->at(array(T_PUBLIC, T_PROTECTED, T_PRIVATE, T_STATIC, T_FINAL, T_ABSTRACT));
    }
    
    private function consume_balanced_block($open, $close) {
        $text = $this->current_text();
        $this->accept($open);
        $depth = 1;
        while (!$this->eof()) {
            $text .= $this->current_text();
            if ($this->at($open)) {
                $depth++;
            } elseif ($this->at($close)) {
                $depth--;
            }
            $this->accept();
            if ($depth == 0) {
                return $text;
            }
        }
        $this->error('unexpected EOF');
    }
    
    private function current_token() {
        return is_array($this->curr) ? $this->curr[0] : $this->curr;
    }
    
    private function current_token_name() {
        $tok = $this->current_token();
        return is_string($tok) ? $tok : token_name($tok);
    }
    
    private function current_text() {
        return is_array($this->curr) ? $this->curr[1] : $this->curr;
    }
    
    private function accept($tok = null, $error = 'parse error') {
        $c = $this->current_token();
        $ok = false;
        if ($tok !== null) {
            if (is_array($tok)) {
                foreach ($tok as $t) {
                    if ($c == $t) $ok = true;
                }
            } elseif ($c == $tok) {
                $ok = true;
            }
        }
        if ($tok === null || $ok) {
            $this->advance();
        } else {
            $this->error($error);
        }
    }
    
    private function advance() {
        // Hacky. Whenever we advance we check for a doc comment, and if it's there
        // we parse it for annotations and stash them. I'd previously had this check
        // in the s() method for skipping ignored tokens but that didn't work outwith
        // a class definition because phpx is essentially dumb and just passes through
        // any tokens it's not interested in.
        if (is_array($this->curr) && $this->curr[0] == T_DOC_COMMENT) {
            $this->last_annotation = null;
            try {
                $parser = new AnnotationParser();
                $annote = $parser->parse($this->current_text());
                if (count($annote)) {
                    $this->last_annotation = $annote;
                }
            } catch (\Exception $e) {
                // TODO: some form of error reporting would be good
            }
        }
        $this->ix++;
        if ($this->ix >= count($this->tokens)) {
            $this->curr = null;
        } else {
            $this->curr = $this->tokens[$this->ix];
        }
    }
    
    private function s() {
        while (in_array($this->current_token(), array(T_WHITESPACE, T_COMMENT, T_DOC_COMMENT))) {
            $this->advance();
        }
    }
    
    private function error($msg) {
        throw new ParseError($msg);
    }
    
    private function write_interface_delegate($class, $variable_name, $interface_name) {
        $ref = new \ReflectionClass($interface_name);
        if (!$ref->isInterface()) {
            $this->error("can't delegate \$$variable_name to $interface_name - not an interface");
        }
        
        foreach ($ref->getMethods() as $method) {
            $class->add_method(Method::create_delegate_for_reflection($method, $variable_name));
        }
    }
}
?>
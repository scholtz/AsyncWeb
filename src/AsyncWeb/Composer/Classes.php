<?php
namespace AsyncWeb\Composer;

class Classes
{
	// derived from code published at http://stackoverflow.com/questions/22761554/php-get-all-class-names-inside-a-particular-namespace

    //This value should be the directory that contains composer.json
    const appRoot = __DIR__ . "/../../../../../../";

    public static function getClassesInNamespace($namespace)
    {
        $files = scandir(self::getNamespaceDirectory($namespace));

        $classes = array_map(function($file) use ($namespace){
            return $namespace . '\\' . str_replace('.php', '', $file);
        }, $files);

        return array_filter($classes, function($possibleClass){
            return class_exists($possibleClass);
        });
    }

    private static function getDefinedNamespaces()
    {
        $composerJsonPath = self::appRoot . 'composer.json';
        $composerConfig = json_decode(file_get_contents($composerJsonPath));

        //Apparently PHP doesn't like hyphens, so we use variable variables instead.
        $psr4 = "psr-4";
        return (array) $composerConfig->autoload->$psr4;
    }

    private static function getNamespaceDirectory($namespace)
    {
		
        $composerNamespaces = self::getDefinedNamespaces();
			
		if(substr($namespace,0,1) == "\\"){
			foreach($composerNamespaces as $k=>$v){
				if(substr($k,0,1) != "\\"){
					$composerNamespaces["\\".$k] = $v;
				}
			}
		}

        $namespaceFragments = explode('\\', $namespace);
        $undefinedNamespaceFragments = [];

        while($namespaceFragments) {
            $possibleNamespace = implode('\\', $namespaceFragments) . '\\';
			
            if(array_key_exists($possibleNamespace, $composerNamespaces)){
				$namespacePath = trim($composerNamespaces[$possibleNamespace],"/");
                return realpath(self::appRoot . $namespacePath ."/" . implode('/', array_reverse($undefinedNamespaceFragments))); 
            }

            $undefinedNamespaceFragments[] = array_pop($namespaceFragments);
        }

        return false;
    }
}
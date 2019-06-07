<?php

namespace InfyOm\Generator\Generators\Domains;

use InfyOm\Generator\Common\CommandData;
use InfyOm\Generator\Generators\BaseGenerator;
use InfyOm\Generator\Generators\SwaggerGenerator;
use InfyOm\Generator\Utils\FileUtil;

class EntityGenerator  extends BaseGenerator
{
    /** @var CommandData */
    private $commandData;

    /** @var string */
    private $path;
    private $fileName;
    private $domainName;
    private $table;
    private $domainNamespace;

    /**
     * ModelGenerator constructor.
     *
     * @param \InfyOm\Generator\Common\CommandData $commandData
     */
    public function __construct(CommandData $commandData)
    {
        $this->commandData = $commandData;
        $this->domainName = $this->commandData->modelName;
        $this->path = $commandData->config->domainsPath . DIRECTORY_SEPARATOR . $this->domainName;
        $this->fileName = $this->domainName .'Entity.php';
        $this->table = $this->commandData->dynamicVars['$TABLE_NAME$'];
        $this->domainNamespace = $this->commandData->dynamicVars['$DOMAINS_NAMESPACE$'].'\\'.$this->domainName;
    }


    public function generate()
    {
        $templateData = get_template('domains.entity', 'laravel-generator');

        $templateData = $this->fillTemplate($templateData);

        FileUtil::createFile($this->path, $this->fileName, $templateData);

        $this->commandData->commandComment("\nEntity created: ");
        $this->commandData->commandInfo($this->fileName);
    }

    /**
     * @param $templateData
     * @return mixed|string
     */
    private function fillTemplate($templateData)
    {
        $templateData = fill_template($this->commandData->dynamicVars, $templateData);

        $templateData = str_replace('$ENTITY_NAMESPACE$', $this->domainNamespace, $templateData);

        $templateData = $this->fillImports($templateData);

        $templateData = $this->fillDocs($templateData);

        $templateData = str_replace('$ENTITY_NAME$', $this->domainName.'Entity', $templateData);

        $templateData = $this->fillProperties($templateData);

        $templateData = $this->fillSetters($templateData);

        $templateData = $this->fillGetters($templateData);

        $templateData = $this->fillToArray($templateData);

        return $templateData;
    }

    /**
     * @param $templateData
     * @return mixed
     */
    private function fillImports($templateData)
    {
        $imports = [
            $this->domainNamespace.'\\'.$this->domainName.'Factory',
            $this->domainNamespace.'\\'.$this->domainName.'Collection',
        ];
        return str_replace('$IMPORT_DOMAIN_STUFF$', implode(';'.infy_nl(), $imports), $templateData);
    }

    /**
     * @param $templateData
     * @return mixed
     */
    private function fillDocs($templateData)
    {
        $template = get_template('model_docs.model', 'swagger-generator');
        $template = fill_template($this->commandData->dynamicVars, $template);
        $template = fill_template(['$REQUIRED_FIELDS$' =>'""','$PROPERTIES$' =>''], $template);
        return str_replace('$DOCS$', $template, $templateData);
    }

    /**
     * @param $templateData
     * @return mixed
     */
    private function fillProperties($templateData)
    {
        $properties = [];
        $templateProperty = get_template('domains.entity_property', 'laravel-generator');
        $templateSwagger = get_template('model_docs.property', 'swagger-generator');
        foreach ($this->commandData->fields as $field){
            $docs = SwaggerGenerator::preparePropertyField($templateSwagger, $field);

            $properties[] = fill_template(
                [
                    '$DOCS$' => $docs,
                    '$PROPERTY_NAME$' => Str::camel($field['name']),
                ],
                $templateProperty
            );
        }
        return str_replace('$DOCS$', implode(infy_nl(), $properties), $templateData);
    }

    /**
     * @param $templateData
     * @return mixed
     */
    private function fillSetters($templateData)
    {
        $methods = [];
        foreach ($this->commandData->fields as $field) {
            $fieldName = Str::camel($field['name']);
            $template = get_template('domains.entity_setter', 'laravel-generator');
            $methods[] = fill_template(
                [
                    '$PROPERTY_TYPE$' => $field['type'],
                    '$PROPERTY$' => $fieldName,
                    '$ENTITY_CLASS$' => $this->domainName.'Entity',
                    '$FUNCTION_NAME$' => ucfirst($fieldName),
                ],
                $template
            );
        }
        return str_replace('$DOCS$', implode(infy_nl(), $methods), $templateData);
    }

    /**
     * @param $templateData
     * @return mixed
     */
    private function fillGetters($templateData)
    {
        $methods = [];
        foreach ($this->commandData->fields as $field) {
            $fieldName = Str::camel($field['name']);
            $template = get_template('domains.entity_getter', 'laravel-generator');
            $methods[] = fill_template(
                [
                    '$PROPERTY_TYPE$' => $field['type'],
                    '$PROPERTY$' => $fieldName,
                    '$FUNCTION_NAME$' => ucfirst($fieldName),
                ],
                $template
            );
        }
        return str_replace('$DOCS$', implode(infy_nl(), $methods), $templateData);
    }

    /**
     * @param $templateData
     * @return mixed
     */
    private function fillToArray($templateData)
    {
        $properties = [];
        foreach ($this->commandData->fields as $field) {
            $fieldName = Str::camel($field['name']);
            $methodName = 'get'.ucfirst($fieldName);
            $properties[] = "'{$fieldName}' => \$this->{$methodName}(),";
        }
        return str_replace('$PROPERTY_CASTS$', implode(infy_tabs(2), $properties), $templateData);
    }
}
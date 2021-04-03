<?php

namespace LdapRecord\Models\Attributes;

use LdapRecord\EscapesValues;

class DistinguishedNameBuilder
{
    use EscapesValues;

    /**
     * The components of the DN.
     *
     * @var array
     */
    protected $components = [];

    /**
     * Whether to output the DN in reverse.
     *
     * @var bool
     */
    protected $reverse = false;

    /**
     * Constructor.
     *
     * @param string|null $value
     */
    public function __construct($dn = null)
    {
        $this->components = array_map(
            [$this, 'explodeRdn'], DistinguishedName::make($dn)->components()
        );
    }

    /**
     * Forward missing method calls onto the Distinguished name object.
     *
     * @param string $method
     * @param array  $args
     *
     * @return mixed
     */
    public function __call($method, $args)
    {
        return $this->get()->{$method}($args);
    }

    /**
     * Get the distinguished name value.
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->get();
    }

    /**
     * Prepend an RDN onto the DN.
     *
     * @param string|array $attribute
     * @param string|null  $value
     *
     * @return $this
     */
    public function prepend($attribute, $value = null)
    {
        array_unshift(
            $this->components,
            ...$this->componentize($attribute, $value)
        );

        return $this;
    }

    /**
     * Append an RDN onto the DN.
     *
     * @param string|array $attribute
     * @param string|null  $value
     *
     * @return $this
     */
    public function append($attribute, $value = null)
    {
        array_push(
            $this->components,
            ...$this->componentize($attribute, $value)
        );

        return $this;
    }

    /**
     * Componentize the attribute and value.
     *
     * @param string|array $attribute
     * @param string|null  $value
     *
     * @return array
     */
    protected function componentize($attribute, $value = null)
    {
        // Here we will make the assumption that an array of
        // RDN's have been given if the value is null, and
        // attempt to break them into their components.
        if (is_null($value)) {
            $attribute = is_array($attribute) ? $attribute : [$attribute];

            $components = array_map([$this, 'explodeRdn'], $attribute);
        } else {
            $components = [[$attribute, $value]];
        }

        return array_map(function ($component) {
            [$attribute, $value] = $component;

            return $this->makeAppendableComponent($attribute, $value);
        }, $components);
    }

    /**
     * Make an appendable component array from the attribute and value.
     *
     * @param string|array $attribute
     * @param string|null  $value
     *
     * @return array
     */
    protected function makeAppendableComponent($attribute, $value = null)
    {
        return [trim($attribute), $this->escape(trim($value))->dn()];
    }

    /**
     * Pop an RDN off of the end of the DN.
     *
     * @param int   $amount
     * @param array $removed
     *
     * @return $this
     */
    public function pop($amount = 1, &$removed = [])
    {
        $removed = array_map(
            [$this, 'makeRdn'],
            array_splice($this->components, -$amount, $amount)
        );

        return $this;
    }

    /**
     * Shift an RDN off of the beginning of the DN.
     *
     * @param int   $amount
     * @param array $removed
     *
     * @return $this
     */
    public function shift($amount = 1, &$removed = [])
    {
        $removed = array_map(
            [$this, 'makeRdn'],
            array_splice($this->components, 0, $amount)
        );

        return $this;
    }

    /**
     * Whether to output the DN in reverse.
     *
     * @return $this
     */
    public function reverse()
    {
        $this->reverse = true;

        return $this;
    }

    /**
     * Get the fully qualified DN.
     *
     * @return DistinguishedName
     */
    public function get()
    {
        return new DistinguishedName($this->build());
    }

    /**
     * Build the distinguished name from the components.
     *
     * @return $this
     */
    protected function build()
    {
        $components = $this->reverse
            ? array_reverse($this->components)
            : $this->components;

        return implode(',', array_map(
            [$this, 'makeRdn'], $components
        ));
    }

    /**
     * Explode the RDN into an attribute and value.
     *
     * @param string $rdn
     *
     * @return string
     */
    protected function explodeRdn($rdn)
    {
        return explode('=', $rdn);
    }

    /**
     * Implode the component attribute and value into an RDN.
     *
     * @param string $rdn
     *
     * @return string
     */
    protected function makeRdn(array $component)
    {
        return implode('=', $component);
    }
}
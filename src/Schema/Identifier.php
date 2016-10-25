<?php
namespace Sojf\DBAL\Schema;

use Sojf\DBAL\Abstracts\Asset;


/**
 * An abstraction class for an asset identifier.
 *
 * Wraps identifier names like column names in indexes / foreign keys
 * in an abstract class for proper quotation capabilities.
 *
 * @author Steve Müller <st.mueller@dzh-online.de>
 * @link   www.doctrine-project.org
 * @since  2.4
 */
class Identifier extends Asset
{
    /**
     * Constructor.
     *
     * @param string $identifier Identifier name to wrap.
     */
    public function __construct($identifier)
    {
        $this->_setName($identifier);
    }
}

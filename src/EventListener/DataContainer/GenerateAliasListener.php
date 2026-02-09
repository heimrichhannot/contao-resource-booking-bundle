<?php

namespace HeimrichHannot\ResourceBookingBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Slug\Slug;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;
use HeimrichHannot\ResourceBookingBundle\Contao\Table;

readonly class GenerateAliasListener
{
    public function __construct(
        private Connection $connection,
        private Slug $slugGenerator,
    ) {}

    #[AsCallback(Table::BOOKING_ARCHIVE->value, 'fields.alias.save')]
    #[AsCallback(Table::RESOURCE_ARCHIVE->value, 'fields.alias.save')]
    public function generateAlias(mixed $value, DataContainer $dc): string
    {
        if (!($id = $dc->id) || !($table = $dc->table)) {
            throw new \RuntimeException('ID or Table not set on DataContainer.');
        }

        $aliasExists = function (string $alias) use ($id, $table): bool {
            $result = $this->connection->createQueryBuilder()
                ->select('id')
                ->from($table)
                ->where('alias = :alias')
                ->setParameter('alias', $alias)
                ->andWhere('id != :id')
                ->setParameter('id', $id)
                ->setMaxResults(1)
                ->executeQuery();

            $result->free();

            return $result->rowCount() > 0;
        };

        if (\preg_match('/^[1-9]\d*$/', $value)) {
            throw new \Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasNumeric'], $value));
        }

        if ($aliasExists($value)) {
            throw new \Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $value));
        }

        return $value ?: $this->slugGenerator->generate($dc->activeRecord->title, [], $aliasExists);
    }
}
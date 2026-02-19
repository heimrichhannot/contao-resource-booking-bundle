<?php

declare(strict_types=1);

namespace HeimrichHannot\ResourceBookingBundle\Command;

use Contao\CoreBundle\Framework\ContaoFramework;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use HeimrichHannot\ResourceBookingBundle\Booking\FinalState;
use HeimrichHannot\ResourceBookingBundle\Booking\Pipeline\BookingPipeline;
use HeimrichHannot\ResourceBookingBundle\Model\BookingModel;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;

#[AsCommand(name: 'huh:rb:pipeline:process', description: 'Processes unfinished bookings')]
class ProcessUnfinishedBookingsCommand extends Command implements ServiceSubscriberInterface
{
    use ServiceSubscriberTrait;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var ContaoFramework $framework */
        if (!$framework = $this->container->get(ContaoFramework::class)) {
            throw new \RuntimeException('Contao framework service not available');
        }

        /** @var Connection $connection */
        if (!$connection = $this->container->get(Connection::class)) {
            throw new \RuntimeException('Database connection not available');
        }

        /** @var BookingPipeline $pipeline */
        if (!$pipeline = $this->container->get(BookingPipeline::class)) {
            throw new \RuntimeException('Booking pipeline not available');
        }

        $framework->initialize();

        $io = new SymfonyStyle($input, $output);

        $io->title('Processing unfinished bookings');

        $table = BookingModel::getTable();
        $finalStatuses = \array_map(static fn (FinalState $state): string => $state->value, FinalState::cases());

        $result = $connection->createQueryBuilder()
            ->select('id')
            ->from($table)
            ->where('status IS NULL')
            ->orWhere('status NOT IN (:statuses)')
            ->setParameter('statuses', $finalStatuses, ArrayParameterType::STRING)
            ->executeQuery();
        $process = $result->fetchFirstColumn();
        $result->free();

        if ($process)
        {
            $io->info(\sprintf('Found %d unfinished bookings.', \count($process)));
            $io->text("{$table}.id");
            $io->listing($process);
        }
        elseif (!$process)
        {
            $io->info('No unfinished bookings found.');
            return Command::SUCCESS;
        }

        if (!$models = BookingModel::findMultipleByIds($process))
        {
            $io->info('No unfinished booking models found.');
            return Command::SUCCESS;
        }

        foreach ($models as $model)
        {
            if (!$model instanceof BookingModel) {
                continue;
            }

            $io->text(\sprintf('┌[ID=%d status="%s"]', $model->id, $model->status));
            $io->text(\sprintf('├─Processing booking...'));

            $result = $pipeline->process($model);

            $io->text(\sprintf(
                '├─Booking processed with result "%s": %s',
                $result->action,
                $result->message ? "\"$result->message\"" : '(No message provided)'
            ));
            $io->text(\sprintf('└[ID=%d status="%s"]', $model->id, $model->status));

            $io->newLine();
        }

        $io->newLine();
        $io->success('Finished processing unfinished bookings.');

        return Command::SUCCESS;
    }

    public static function getSubscribedServices(): array
    {
        return [
            Connection::class,
            ContaoFramework::class,
            BookingPipeline::class,
        ];
    }
}

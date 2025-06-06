<?php

declare(strict_types=1);

namespace AppBundle\Command;

use AppBundle\Event\Model\Event;
use AppBundle\Event\Model\Repository\EventRepository;
use AppBundle\Event\Model\Repository\EventStatsRepository;
use AppBundle\Event\Model\Repository\TicketTypeRepository;
use AppBundle\Notifier\SlackNotifier;
use AppBundle\Slack\MessageFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TicketStatsNotificationCommand extends Command
{
    public function __construct(
        private readonly MessageFactory $messageFactory,
        private readonly EventStatsRepository $eventStatsRepository,
        private readonly SlackNotifier $slackNotifier,
        private readonly EventRepository $eventRepository,
        private readonly TicketTypeRepository $ticketTypeRepository,
    ) {
        parent::__construct();
    }
    /**
     * @see Command
     */
    protected function configure(): void
    {
        $this
            ->setName('ticket-stats-notification')
            ->addOption('display-diff', null, InputOption::VALUE_NONE)
        ;
    }

    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $date = null;

        if ($input->getOption('display-diff')) {
            $date = new \DateTime();
            $date->modify('- 1 day');
        }

        /** @var Event $event */
        foreach ($this->eventRepository->getNextEvents() as $event) {
            $message = $this->messageFactory->createMessageForTicketStats(
                $event,
                $this->eventStatsRepository,
                $this->ticketTypeRepository,
                $date,
            );

            $this->slackNotifier->sendMessage($message);
        }

        return Command::SUCCESS;
    }
}

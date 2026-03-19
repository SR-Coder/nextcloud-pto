<?php

declare(strict_types=1);

namespace OCA\PTO\Notification;

use OCA\PTO\AppInfo\Application;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;

class Notifier implements INotifier {
    private IFactory $l10nFactory;
    private IURLGenerator $urlGenerator;

    public function __construct(IFactory $l10nFactory, IURLGenerator $urlGenerator) {
        $this->l10nFactory = $l10nFactory;
        $this->urlGenerator = $urlGenerator;
    }

    public function getID(): string {
        return Application::APP_ID;
    }

    public function getName(): string {
        return $this->l10nFactory->get(Application::APP_ID)->t('PTO Tracker');
    }

    public function prepare(INotification $notification, string $languageCode): INotification {
        if ($notification->getApp() !== Application::APP_ID) {
            throw new \InvalidArgumentException('Incorrect app');
        }

        $l = $this->l10nFactory->get(Application::APP_ID, $languageCode);

        switch ($notification->getSubject()) {
            case 'request_submitted':
                return $this->prepareRequestSubmitted($notification, $l);
            case 'request_approved':
                return $this->prepareRequestApproved($notification, $l);
            case 'request_denied':
                return $this->prepareRequestDenied($notification, $l);
            default:
                throw new \InvalidArgumentException('Unknown subject');
        }
    }

    private function prepareRequestSubmitted(INotification $notification, $l): INotification {
        $params = $notification->getSubjectParameters();
        
        $notification->setParsedSubject(
            $l->t('%s submitted a PTO request', [$params['requester']])
        );
        
        $notification->setParsedMessage(
            $l->t('%s hours from %s to %s', [
                $params['hours'],
                $params['startDate'],
                $params['endDate']
            ])
        );

        $notification->setIcon($this->urlGenerator->getAbsoluteURL(
            $this->urlGenerator->imagePath(Application::APP_ID, 'app.svg')
        ));

        $notification->setLink($this->urlGenerator->linkToRouteAbsolute(
            Application::APP_ID . '.page.index'
        ) . '#/approvals');

        // Add approve/deny actions
        $notification->addAction($notification->createAction()
            ->setLabel('approve')
            ->setParsedLabel($l->t('Approve'))
            ->setLink(
                $this->urlGenerator->linkToOCSRouteAbsolute(
                    Application::APP_ID . '.request.approve',
                    ['id' => $params['requestId']]
                ),
                'POST'
            )
            ->setPrimary(true)
        );

        $notification->addAction($notification->createAction()
            ->setLabel('deny')
            ->setParsedLabel($l->t('Deny'))
            ->setLink(
                $this->urlGenerator->linkToOCSRouteAbsolute(
                    Application::APP_ID . '.request.deny',
                    ['id' => $params['requestId']]
                ),
                'POST'
            )
            ->setPrimary(false)
        );

        return $notification;
    }

    private function prepareRequestApproved(INotification $notification, $l): INotification {
        $params = $notification->getSubjectParameters();
        
        $notification->setParsedSubject(
            $l->t('Your PTO request was approved')
        );
        
        $message = $l->t('%s hours from %s to %s', [
            $params['hours'],
            $params['startDate'],
            $params['endDate']
        ]);

        if (!empty($params['comments'])) {
            $message .= "\n\n" . $l->t('Manager comment: %s', [$params['comments']]);
        }

        $notification->setParsedMessage($message);

        $notification->setIcon($this->urlGenerator->getAbsoluteURL(
            $this->urlGenerator->imagePath(Application::APP_ID, 'app.svg')
        ));

        $notification->setLink($this->urlGenerator->linkToRouteAbsolute(
            Application::APP_ID . '.page.index'
        ) . '#/requests');

        return $notification;
    }

    private function prepareRequestDenied(INotification $notification, $l): INotification {
        $params = $notification->getSubjectParameters();
        
        $notification->setParsedSubject(
            $l->t('Your PTO request was denied')
        );
        
        $message = $l->t('%s hours from %s to %s', [
            $params['hours'],
            $params['startDate'],
            $params['endDate']
        ]);

        if (!empty($params['comments'])) {
            $message .= "\n\n" . $l->t('Manager comment: %s', [$params['comments']]);
        }

        $notification->setParsedMessage($message);

        $notification->setIcon($this->urlGenerator->getAbsoluteURL(
            $this->urlGenerator->imagePath(Application::APP_ID, 'app.svg')
        ));

        $notification->setLink($this->urlGenerator->linkToRouteAbsolute(
            Application::APP_ID . '.page.index'
        ) . '#/requests');

        return $notification;
    }
}

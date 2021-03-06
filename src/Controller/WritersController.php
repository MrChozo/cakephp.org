<?php
namespace App\Controller;

use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Mailer\Email;
use ReCaptcha\ReCaptcha;

/**
 * Writers Controller
 *
 * @property \App\Model\Table\WritersTable $Writers
 */
class WritersController extends AppController
{

    public function beforeFilter(Event $event)
    {
        $this->Auth->allow();

        return parent::beforeFilter($event);
    }

    /**
     * Index method
     *
     * @return \Cake\Network\Response|null
     */
    public function index()
    {
        $writer = $this->Writers->newEntity();

        if (Configure::read('Site.writers_form_enabled') && $this->request->is('post')) {
            $recaptcha = new ReCaptcha(Configure::read('ReCaptcha.secret_key'));
            $resp = $recaptcha->verify($this->request->data('g-recaptcha-response'), $this->request->clientIp());
            if ($resp->isSuccess()) {
                $writer = $this->Writers->newEntity($this->request->data);
                $writer->client_ip = $this->request->clientIp();
                if ($this->Writers->save($writer)) {

                    $email = new Email('default');
                    $email
                        ->emailFormat('text')
                        ->replyTo($writer->email, $writer->name)
                        ->from([Configure::read('Site.contact.marketing_email') => __('CakePHP Website')])
                        ->to(Configure::read('Site.contact.marketing_email'))
                        ->subject(__('Writers Form'))
                        ->set(compact('writer'))
                        ->template('writers_form')
                        ->send();

                    $this->Flash->success(__('Thanks for your submission! We will review and get back to you shortly!'));

                    return $this->redirect(['action' => 'index']);
                }
            } else {
                $this->Flash->error(__('Please check your Recaptcha Box.'));
            }
        }

        $this->set(compact('writer'));
    }
}

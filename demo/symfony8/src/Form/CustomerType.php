<?php

declare(strict_types=1);

namespace App\Form;

use Nowo\FormKitBundle\Attribute\FormKitConfig;
use Nowo\FormKitBundle\Form\FormOptionsTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * "Create customer" form of the demo (REQ-TWIG-005: rendered with form_start / child loop / form_end). REQ-DEMO-015: a
 * FormKit form (profile `demo`) on UiKit's form theme.
 *
 * @extends AbstractType<array{name: string, email: string}>
 */
#[FormKitConfig('demo')]
final class CustomerType extends AbstractType
{
    use FormOptionsTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addText($builder, 'name', [
            'label'              => 'Name',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'constraints'        => [new NotBlank(), new Length(max: 100)],
        ]);
        $this->addEmail($builder, 'email', [
            'label'              => 'Email',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'constraints'        => [new NotBlank(), new Email()],
        ]);
        $this->addWithDefaults($builder, 'submit', SubmitType::class, [
            'label'              => 'Create customer',
            'translation_domain' => false,
            'attr'               => ['class' => 'nowo-ui-btn nowo-ui-btn--primary'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_token_id'   => 'openpay_demo_customer',
        ]);
    }
}

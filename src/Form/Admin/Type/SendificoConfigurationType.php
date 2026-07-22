<?php

namespace Vx\Sendifico\Form\Admin\Type;

use PrestaShopBundle\Form\Admin\Type\MultistoreConfigurationType;
use PrestaShopBundle\Form\Admin\Type\SwitchType;
use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;

class SendificoConfigurationType extends TranslatorAwareType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $decimalConstraint = new Regex([
            'pattern' => '/^\d+(\.\d{1,3})?$/',
            'message' => $this->trans('Use a positive decimal value with up to 3 decimals.', 'Modules.Vxsendifico.Admin'),
        ]);

        $builder
            ->add('api_key', TextType::class, [
                'required' => false,
                'multistore_configuration_key' => 'VX_SENDIFICO_API_KEY',
                'label' => $this->trans('API key', 'Modules.Vxsendifico.Admin'),
                'help' => $this->trans('Clave privada usada para autenticar las llamadas a Sendifico.', 'Modules.Vxsendifico.Admin'),
                'constraints' => [
                    new Length([
                        'max' => 255,
                    ]),
                ],
            ])
            ->add('api_version', TextType::class, [
                'required' => true,
                'multistore_configuration_key' => 'VX_SENDIFICO_API_VERSION',
                'label' => $this->trans('API version', 'Modules.Vxsendifico.Admin'),
                'help' => $this->trans('Version contractual enviada en headers, con formato YYYY-MM-DD.', 'Modules.Vxsendifico.Admin'),
                'constraints' => [
                    new Regex([
                        'pattern' => '/^\d{4}-\d{2}-\d{2}$/',
                        'message' => $this->trans('Use the YYYY-MM-DD format.', 'Modules.Vxsendifico.Admin'),
                    ]),
                ],
            ])
            ->add('country', TextType::class, [
                'required' => true,
                'multistore_configuration_key' => 'VX_SENDIFICO_COUNTRY',
                'label' => $this->trans('Country', 'Modules.Vxsendifico.Admin'),
                'help' => $this->trans('Codigo ISO de 2 letras usado para Sendifico. Para v1 se espera EC.', 'Modules.Vxsendifico.Admin'),
                'attr' => ['maxlength' => 2],
                'constraints' => [
                    new Regex([
                        'pattern' => '/^[A-Z]{2}$/',
                        'message' => $this->trans('Use a 2-letter uppercase ISO code.', 'Modules.Vxsendifico.Admin'),
                    ]),
                ],
            ])
            ->add('currency', TextType::class, [
                'required' => true,
                'multistore_configuration_key' => 'VX_SENDIFICO_CURRENCY',
                'label' => $this->trans('Currency', 'Modules.Vxsendifico.Admin'),
                'help' => $this->trans('Codigo ISO de 3 letras para cotizaciones y persistencia. Para v1 se espera USD.', 'Modules.Vxsendifico.Admin'),
                'attr' => ['maxlength' => 3],
                'constraints' => [
                    new Regex([
                        'pattern' => '/^[A-Z]{3}$/',
                        'message' => $this->trans('Use a 3-letter uppercase ISO code.', 'Modules.Vxsendifico.Admin'),
                    ]),
                ],
            ])
            ->add('sender_reference', TextType::class, [
                'required' => false,
                'multistore_configuration_key' => 'VX_SENDIFICO_SENDER_REFERENCE',
                'label' => $this->trans('Sender reference', 'Modules.Vxsendifico.Admin'),
                'help' => $this->trans('Identificador o referencia exacta del sender de Sendifico asociado a esta tienda.', 'Modules.Vxsendifico.Admin'),
                'constraints' => [
                    new Length([
                        'max' => 255,
                    ]),
                ],
            ])
            ->add('cod_payment_methods', TextareaType::class, [
                'required' => false,
                'multistore_configuration_key' => 'VX_SENDIFICO_COD_PAYMENT_METHODS',
                'label' => $this->trans('COD payment methods', 'Modules.Vxsendifico.Admin'),
                'help' => $this->trans('Lista de nombres tecnicos de modulos de pago que deben tratarse como contra entrega. Separe por linea o coma.', 'Modules.Vxsendifico.Admin'),
                'attr' => [
                    'rows' => 4,
                ],
            ])
            ->add('allow_incomplete_checkout_address', SwitchType::class, [
                'required' => false,
                'multistore_configuration_key' => 'VX_SENDIFICO_ALLOW_INCOMPLETE_CHECKOUT_ADDRESS',
                'label' => $this->trans('Allow checkout quote with incomplete address', 'Modules.Vxsendifico.Admin'),
                'help' => $this->trans('Permite cotizar mientras faltan datos criticos y bloquear solo la creacion del shipment.', 'Modules.Vxsendifico.Admin'),
            ])
            ->add('purchase_with', ChoiceType::class, [
                'required' => true,
                'multistore_configuration_key' => 'VX_SENDIFICO_PURCHASE_WITH',
                'label' => $this->trans('Purchase source', 'Modules.Vxsendifico.Admin'),
                'help' => $this->trans('Define la politica operativa para comprar el envio cuando el pedido entra en estado pagado.', 'Modules.Vxsendifico.Admin'),
                'choices' => [
                    $this->trans('Wallet available', 'Modules.Vxsendifico.Admin') => 'walletAvailable',
                    $this->trans('Cash', 'Modules.Vxsendifico.Admin') => 'cash',
                ],
                'constraints' => [
                    new Choice([
                        'choices' => ['walletAvailable', 'cash'],
                    ]),
                ],
            ])
            ->add('auto_purchase_enabled', SwitchType::class, [
                'required' => false,
                'multistore_configuration_key' => 'VX_SENDIFICO_AUTO_PURCHASE_ENABLED',
                'label' => $this->trans('Enable automatic purchase', 'Modules.Vxsendifico.Admin'),
                'help' => $this->trans('Si esta habilitado, el modulo intentara comprar el shipment al entrar el pedido en estado pagado/aceptado.', 'Modules.Vxsendifico.Admin'),
            ])
            ->add('default_weight', TextType::class, [
                'required' => true,
                'multistore_configuration_key' => 'VX_SENDIFICO_DEFAULT_WEIGHT',
                'label' => $this->trans('Default weight (kg)', 'Modules.Vxsendifico.Admin'),
                'help' => $this->trans('Fallback usado cuando el producto no tiene peso configurado.', 'Modules.Vxsendifico.Admin'),
                'constraints' => [$decimalConstraint],
            ])
            ->add('default_length', TextType::class, [
                'required' => true,
                'multistore_configuration_key' => 'VX_SENDIFICO_DEFAULT_LENGTH',
                'label' => $this->trans('Default length (cm)', 'Modules.Vxsendifico.Admin'),
                'constraints' => [$decimalConstraint],
            ])
            ->add('default_width', TextType::class, [
                'required' => true,
                'multistore_configuration_key' => 'VX_SENDIFICO_DEFAULT_WIDTH',
                'label' => $this->trans('Default width (cm)', 'Modules.Vxsendifico.Admin'),
                'constraints' => [$decimalConstraint],
            ])
            ->add('default_height', TextType::class, [
                'required' => true,
                'multistore_configuration_key' => 'VX_SENDIFICO_DEFAULT_HEIGHT',
                'label' => $this->trans('Default height (cm)', 'Modules.Vxsendifico.Admin'),
                'constraints' => [$decimalConstraint],
            ])
            ->add('enable_debug_logs', SwitchType::class, [
                'required' => false,
                'multistore_configuration_key' => 'VX_SENDIFICO_ENABLE_DEBUG_LOGS',
                'label' => $this->trans('Enable debug logs', 'Modules.Vxsendifico.Admin'),
                'help' => $this->trans('Registra trazabilidad tecnica adicional. No almacene secretos ni payloads sensibles completos.', 'Modules.Vxsendifico.Admin'),
            ])
            ->add('log_retention_days', IntegerType::class, [
                'required' => true,
                'multistore_configuration_key' => 'VX_SENDIFICO_LOG_RETENTION_DAYS',
                'label' => $this->trans('Log retention (days)', 'Modules.Vxsendifico.Admin'),
                'help' => $this->trans('Cantidad de dias que deben mantenerse los logs operativos del modulo.', 'Modules.Vxsendifico.Admin'),
                'constraints' => [
                    new GreaterThan([
                        'value' => 0,
                        'message' => $this->trans('Use a value greater than 0.', 'Modules.Vxsendifico.Admin'),
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'translation_domain' => 'Modules.Vxsendifico.Admin',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'vx_sendifico_configuration';
    }

    public function getParent(): string
    {
        return MultistoreConfigurationType::class;
    }
}

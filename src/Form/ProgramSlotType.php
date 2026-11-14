<?php

namespace App\Form;

use App\Entity\ProgramSlot;
use App\Station\ScheduleSlot;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ProgramSlotType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['label' => 'Show'])
            ->add('hostName', TextType::class, ['label' => 'Host'])
            ->add('genre', TextType::class, ['label' => 'Genre'])
            ->add('startHour', ChoiceType::class, [
                'label' => 'Starts',
                'choices' => $this->hours(false),
            ])
            ->add('endHour', ChoiceType::class, [
                'label' => 'Ends',
                'choices' => $this->hours(true),
            ])
            ->add('weekdays', ChoiceType::class, [
                'label' => 'Days',
                'choices' => ProgramSlot::WEEKDAYS,
                'multiple' => true,
                'expanded' => true,
                'attr' => ['class' => 'admin-days'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProgramSlot::class,
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function hours(bool $includeEndOfDay): array
    {
        $choices = [];
        $last = $includeEndOfDay ? 24 : 23;

        for ($hour = 0; $hour <= $last; ++$hour) {
            $label = ScheduleSlot::formatHour($hour);
            if (24 === $hour) {
                $label = '12 AM (end of day)';
            }
            $choices[$label] = $hour;
        }

        return $choices;
    }
}

import * as React from 'react';

import { CheckIcon, ChevronDownIcon } from 'lucide-react';
import * as RPNInput from 'react-phone-number-input';
import flags from 'react-phone-number-input/flags';

import { Button } from '@/components/ui/button';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { Drawer, DrawerContent, DrawerDescription, DrawerHeader, DrawerTitle, DrawerTrigger } from '@/components/ui/drawer';
import { Input } from '@/components/ui/input';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { ReactiveHiddenInput } from '@/components/admin/reactive-hidden-input';
import { ScrollArea } from '@/components/ui/scroll-area';
import { useInDialogLayer } from '@/contexts/dialog-context';
import { useCountries } from '@/hooks/use-countries';
import { useIsMobile } from '@/hooks/admin/use-mobile';
import { usePhoneCountries } from '@/hooks/use-phone-countries';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';

type PhoneInputProps = Omit<React.ComponentProps<'input'>, 'onChange' | 'value' | 'ref'> &
    Omit<RPNInput.Props<typeof RPNInput.default>, 'onChange'> & {
        onChange?: (value: RPNInput.Value) => void;
    };

function PhoneInput({ className, onChange, value, name, countries, ...props }: PhoneInputProps) {
    const phoneCountries = usePhoneCountries();

    return (
        <>
            <RPNInput.default
                countries={countries ?? phoneCountries}
                className={cn(
                    'border-input flex h-9 w-full items-center rounded-md border bg-transparent shadow-xs transition-[color,box-shadow] focus-within:border-ring focus-within:ring-ring/50 focus-within:ring-[3px]',
                    className,
                )}
                flagComponent={FlagComponent}
                countrySelectComponent={CountrySelect}
                inputComponent={InputComponent}
                smartCaret={false}
                international
                value={value || undefined}
                onChange={(phone) => onChange?.(phone || ('' as RPNInput.Value))}
                {...props}
            />
            {name && <ReactiveHiddenInput name={name} value={(value as string) ?? ''} />}
        </>
    );
}

const InputComponent = React.forwardRef<HTMLInputElement, React.ComponentProps<'input'>>(({ className, ...props }, ref) => (
    <Input
        ref={ref}
        className={cn('h-full border-0 bg-transparent ps-1 shadow-none focus-visible:border-0 focus-visible:ring-0', className)}
        {...props}
    />
));
InputComponent.displayName = 'InputComponent';

interface CountrySelectProps {
    disabled?: boolean;
    value: RPNInput.Country;
    options: { label: string; value: RPNInput.Country }[];
    onChange: (value: RPNInput.Country) => void;
}

function CountrySelect({ disabled, value, options, onChange }: CountrySelectProps) {
    const isMobile = useIsMobile();
    const inDialogLayer = useInDialogLayer();
    const [open, setOpen] = React.useState(false);

    const handleSelect = (country: RPNInput.Country) => {
        onChange(country);
        setOpen(false);
    };

    const trigger = (
        <Button
            type="button"
            variant="ghost"
            className="h-full gap-1 rounded-none ps-3 has-[>svg]:pe-1 hover:bg-transparent focus-visible:ring-0 dark:hover:bg-transparent"
            disabled={disabled}
        >
            <FlagComponent country={value} countryName={value} />
            <ChevronDownIcon className={cn('size-4 shrink-0 text-muted-foreground opacity-50', disabled && 'hidden')} />
        </Button>
    );

    if (isMobile) {
        return (
            <Drawer open={open} onOpenChange={setOpen} shouldScaleBackground={true} repositionInputs={false}>
                <DrawerTrigger asChild>{trigger}</DrawerTrigger>
                <DrawerContent>
                    <DrawerHeader>
                        <DrawerTitle>{__('Select a country')}</DrawerTitle>
                        <DrawerDescription className="sr-only">{__('Select a country')}</DrawerDescription>
                    </DrawerHeader>
                    <div className="pb-4">
                        <CountryOptions options={options} value={value} onChange={handleSelect} mobile />
                    </div>
                </DrawerContent>
            </Drawer>
        );
    }

    return (
        <Popover open={open} onOpenChange={setOpen} modal={inDialogLayer}>
            <PopoverTrigger asChild>{trigger}</PopoverTrigger>
            <PopoverContent className="w-72 p-0" align="start">
                <CountryOptions options={options} value={value} onChange={handleSelect} />
            </PopoverContent>
        </Popover>
    );
}

interface CountryOptionsProps extends Omit<CountrySelectProps, 'disabled' | 'onChange'> {
    onChange: (value: RPNInput.Country) => void;
    mobile?: boolean;
}

function CountryOptions({ options, value, onChange, mobile }: CountryOptionsProps) {
    const { countryNames } = useCountries();
    const groupRef = React.useRef<HTMLDivElement>(null);
    const [scrollable, setScrollable] = React.useState(false);

    React.useEffect(() => {
        const group = groupRef.current;
        if (!group) return;

        const viewport = group.closest<HTMLElement>('[data-radix-scroll-area-viewport]');
        const target = viewport ?? group;
        const update = () => setScrollable(target.scrollHeight > target.clientHeight + 1);
        update();

        const observer = new ResizeObserver(update);
        observer.observe(group);
        if (viewport) observer.observe(viewport);

        return () => observer.disconnect();
    }, [options]);

    return (
        <Command defaultValue={value}>
            <CommandInput placeholder={__('Search...')} className={mobile ? 'mx-4' : undefined} />
            <CommandEmpty>{__('No items found.')}</CommandEmpty>
            <CommandList>
                <ScrollArea className="[&_[data-radix-scroll-area-viewport]>div]:block!">
                    <CommandGroup
                        ref={groupRef}
                        className={cn('max-h-75 overflow-visible', scrollable && 'pe-2.5', mobile && '**:[[cmdk-item]]:px-4')}
                    >
                        {options
                            .filter((option) => option.value)
                            .map((option) => (
                                <CommandItem
                                    key={option.value}
                                    value={option.value}
                                    keywords={[option.label, RPNInput.getCountryCallingCode(option.value)]}
                                    className="gap-2 pe-2 [contain-intrinsic-size:auto_2.25rem] [content-visibility:auto]"
                                    onSelect={() => onChange(option.value)}
                                >
                                    <CountryFlag country={option.value} countryName={option.label} />
                                    <span className="min-w-0 truncate text-sm">
                                        {countryNames[option.value] ?? option.label}
                                    </span>
                                    <span className="text-muted-foreground shrink-0 text-sm">
                                        +{RPNInput.getCountryCallingCode(option.value)}
                                    </span>
                                    {option.value === value && <CheckIcon className="ms-auto size-4 shrink-0" />}
                                </CommandItem>
                            ))}
                    </CommandGroup>
                </ScrollArea>
            </CommandList>
        </Command>
    );
}

function FlagComponent({ country, countryName }: RPNInput.FlagProps) {
    const Flag = flags[country];

    return (
        <span className="flex h-4 w-6 shrink-0 items-center justify-center overflow-hidden rounded-xs [&>svg]:h-full [&>svg]:w-auto">
            {Flag ? <Flag title={countryName} /> : <span className="bg-muted size-full" />}
        </span>
    );
}

function CountryFlag({ country, countryName }: { country: RPNInput.Country; countryName: string }) {
    const ref = React.useRef<HTMLSpanElement>(null);
    const [show, setShow] = React.useState(false);

    React.useEffect(() => {
        if (show) return;
        const el = ref.current;
        if (!el) return;

        const observer = new IntersectionObserver(
            (entries) => {
                if (entries[0]?.isIntersecting) {
                    setShow(true);
                    observer.disconnect();
                }
            },
            { root: el.closest<HTMLElement>('[data-radix-scroll-area-viewport]'), rootMargin: '300px' },
        );
        observer.observe(el);

        return () => observer.disconnect();
    }, [show]);

    const Flag = flags[country];

    return (
        <span
            ref={ref}
            className="flex h-4 w-6 shrink-0 items-center justify-center overflow-hidden rounded-xs [&>svg]:h-full [&>svg]:w-auto"
        >
            {show && Flag ? <Flag title={countryName} /> : <span className="bg-muted size-full" />}
        </span>
    );
}

export { PhoneInput };

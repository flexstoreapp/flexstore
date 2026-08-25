import { SearchIcon } from 'lucide-react';
import type { Ref } from 'react';

import { Spinner } from './spinner';
import { InputGroup } from '@/components/ui/input-group';
import { __ } from '@/lib/i18n';

interface SearchInputProps {
    value: string;
    onChange: (value: string) => void;
    loading?: boolean;
    placeholder?: string;
    className?: string;
    ref?: Ref<HTMLInputElement>;
}

function SearchInput({ value, onChange, placeholder = __('Search...'), loading = false, className, ref }: SearchInputProps) {
    const handleKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
        if (e.key === 'Enter') e.preventDefault();
    };

    return (
        <InputGroup.Root className={className}>
            <InputGroup.Prefix>
                <SearchIcon className="size-4" />
            </InputGroup.Prefix>
            <InputGroup.Control
                ref={ref}
                placeholder={placeholder}
                value={value}
                onChange={(e) => onChange(e.target.value)}
                onKeyDown={handleKeyDown}
                role="searchbox"
                aria-busy={loading}
            />
            {loading && (
                <InputGroup.Suffix>
                    <Spinner />
                </InputGroup.Suffix>
            )}
        </InputGroup.Root>
    );
}

export { SearchInput };

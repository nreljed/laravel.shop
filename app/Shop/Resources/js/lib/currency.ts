import { usePage } from "@inertiajs/react";

export interface CurrencyInfo {
    code: string;
    name: string;
    symbol: string;
    exchange_rate: number;
    decimal_places: number;
    is_default: boolean;
}

interface PageWithCurrency {
    currencies?: CurrencyInfo[];
    selectedCurrency?: string;
    currencyPosition?: string;
}

function round(value: number, decimals: number): number {
    const factor = 10 ** decimals;
    return Math.round((value + Number.EPSILON) * factor) / factor;
}

const FALLBACK_CURRENCY: CurrencyInfo = {
    code: "EUR",
    name: "Euro",
    symbol: "€",
    exchange_rate: 1,
    decimal_places: 2,
    is_default: true,
};

export function useCurrency() {
    const { props } = usePage<PageWithCurrency>();
    const currencies = props.currencies ?? [];
    const selectedCode = props.selectedCurrency;
    const position = props.currencyPosition ?? "before";

    const active =
        currencies.find((c) => c.code === selectedCode) ??
        currencies.find((c) => c.is_default) ??
        currencies[0] ??
        FALLBACK_CURRENCY;

    const convert = (amount: number | string | null | undefined): number => {
        const value = Number(amount);
        if (!Number.isFinite(value)) return 0;
        const rate = active.exchange_rate ?? 1;
        return rate === 1 ? value : round(value * rate, active.decimal_places ?? 2);
    };

    const format = (
        value: number | string | null | undefined,
        options: { symbol?: boolean; decimals?: number } = {},
    ): string => {
        const amount = Number(value);
        if (!Number.isFinite(amount)) return "";
        const decimals = options.decimals ?? active.decimal_places ?? 2;
        const formatted = amount.toLocaleString("en-US", {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals,
        });
        if (options.symbol === false) return formatted;
        return position === "after"
            ? `${formatted} ${active.symbol}`.trim()
            : `${active.symbol}${formatted}`;
    };

    const money = (amount: number | string | null | undefined): string =>
        format(convert(amount));

    return { currencies, selectedCode, active, convert, format, money };
}

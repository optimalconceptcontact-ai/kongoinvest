export type StatisticsKpi = {
    key: string;
    label: string;
    value: number;
    format: 'integer' | 'currency' | 'percent' | string;
    variation: number | null;
    variation_label: string | null;
    hint: string | null;
    currency: string | null;
};

export type ChartSeries = {
    key: string;
    label: string;
    color: string;
};

export type ChartDatum = {
    label?: string;
    bucket?: string;
    name?: string;
    value?: number;
    color?: string;
    [key: string]: string | number | undefined;
};

export type StatisticsChart = {
    key: string;
    type: 'line' | 'area' | 'bar' | 'pie' | string;
    title: string;
    description: string;
    data: ChartDatum[];
    series: ChartSeries[];
};

export type TableColumn = {
    key: string;
    label: string;
    sortable: boolean;
    type: string;
    currency_key?: string;
};

export type TableRow = Record<string, string | number | boolean | null> & {
    id?: number;
};

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type PaginatedTable = {
    data: TableRow[];
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
    current_page: number;
    last_page: number;
    per_page: number;
};

export type StatisticsPeriodInfo = {
    preset: string;
    preset_label: string;
    granularity: 'day' | 'month' | string;
    timezone: string;
    start: string;
    end: string;
    previous_start: string;
    previous_end: string;
    label: string;
    previous_label: string;
    days: number;
};

export type StatisticsReport = {
    key: string;
    title: string;
    description: string;
    period: StatisticsPeriodInfo;
    filters: Record<string, unknown>;
    filter_labels: Record<string, string>;
    kpis: StatisticsKpi[];
    charts: StatisticsChart[];
    table: {
        columns: TableColumn[];
        rows: TableRow[];
        total: number;
    };
    generated_at: string;
};

export type FilterOption = {
    value: string;
    label: string;
};

export type FilterField = {
    key: string;
    label: string;
    type?: 'select' | 'text';
    all_label?: string;
    placeholder?: string;
    max_length?: number;
    options?: FilterOption[];
};

export type StatisticsOptions = {
    search_placeholder: string;
    filters: FilterField[];
    can_export: boolean;
};

export type StatisticsFilters = Record<string, string | number | undefined>;

export type StatisticsPageProps = {
    report: StatisticsReport;
    table: PaginatedTable;
    filters: StatisticsFilters;
    options: StatisticsOptions;
    path: string;
    export_path: string;
    note: string | null;
};

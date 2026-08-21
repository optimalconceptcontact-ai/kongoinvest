// Mirrors App\Enums\DrcProvince (26 provinces, 2015 redistricting) —
// single source of truth for the province select, its labels, and the
// per-province city list, shared by the project form and the public
// project card/detail pages.
export const DRC_PROVINCES: [string, string][] = [
    ['kinshasa', 'Kinshasa'],
    ['kongo_central', 'Kongo-Central'],
    ['kwango', 'Kwango'],
    ['kwilu', 'Kwilu'],
    ['mai_ndombe', 'Mai-Ndombe'],
    ['kasai', 'Kasaï'],
    ['kasai_central', 'Kasaï-Central'],
    ['kasai_oriental', 'Kasaï-Oriental'],
    ['lomami', 'Lomami'],
    ['sankuru', 'Sankuru'],
    ['maniema', 'Maniema'],
    ['sud_kivu', 'Sud-Kivu'],
    ['nord_kivu', 'Nord-Kivu'],
    ['ituri', 'Ituri'],
    ['haut_uele', 'Haut-Uélé'],
    ['tshopo', 'Tshopo'],
    ['bas_uele', 'Bas-Uélé'],
    ['nord_ubangi', 'Nord-Ubangi'],
    ['mongala', 'Mongala'],
    ['sud_ubangi', 'Sud-Ubangi'],
    ['equateur', 'Équateur'],
    ['tshuapa', 'Tshuapa'],
    ['haut_katanga', 'Haut-Katanga'],
    ['haut_lomami', 'Haut-Lomami'],
    ['lualaba', 'Lualaba'],
    ['tanganyika', 'Tanganyika'],
];

export const DRC_PROVINCE_CITIES: Record<string, string[]> = {
    kinshasa: ['Kinshasa'],
    kongo_central: ['Matadi', 'Boma', 'Moanda', 'Tshela'],
    kwango: ['Kenge', 'Kasongo-Lunda', 'Popokabaka'],
    kwilu: ['Bandundu', 'Kikwit', 'Idiofa'],
    mai_ndombe: ['Inongo', 'Nioki', 'Kiri'],
    kasai: ['Luebo', 'Tshikapa', 'Ilebo'],
    kasai_central: ['Kananga', 'Dimbelenge', 'Dibaya'],
    kasai_oriental: ['Mbuji-Mayi', 'Kabeya-Kamwanga', 'Miabi'],
    lomami: ['Kabinda', 'Mwene-Ditu', 'Luputa', 'Ngandajika'],
    sankuru: ['Lusambo', 'Lodja', 'Lubefu'],
    maniema: ['Kindu', 'Kailo', 'Kasongo'],
    sud_kivu: ['Bukavu', 'Uvira', 'Baraka'],
    nord_kivu: ['Goma', 'Butembo', 'Beni'],
    ituri: ['Bunia', 'Mahagi', 'Aru'],
    haut_uele: ['Isiro', 'Wamba', 'Watsa'],
    tshopo: ['Kisangani', 'Bafwasende', 'Yahuma'],
    bas_uele: ['Buta', 'Aketi', 'Bondo'],
    nord_ubangi: ['Gbadolite', 'Businga', 'Yakoma'],
    mongala: ['Lisala', 'Bumba', 'Bongandanga'],
    sud_ubangi: ['Gemena', 'Zongo', 'Libenge'],
    equateur: ['Mbandaka', 'Basankusu', 'Bikoro'],
    tshuapa: ['Boende', 'Ikela', 'Monkoto'],
    haut_katanga: ['Lubumbashi', 'Likasi', 'Kipushi'],
    haut_lomami: ['Kamina', 'Kaniama', 'Malemba-Nkulu'],
    lualaba: ['Kolwezi', 'Dilolo', 'Sandoa'],
    tanganyika: ['Kalemie', 'Kabalo', 'Moba'],
};

const LABELS = Object.fromEntries(DRC_PROVINCES);

export function provinceLabel(value: string): string {
    return LABELS[value] ?? value;
}

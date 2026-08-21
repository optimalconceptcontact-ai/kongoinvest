<?php

namespace App\Enums;

enum DrcProvince: string
{
    case Kinshasa = 'kinshasa';
    case KongoCentral = 'kongo_central';
    case Kwango = 'kwango';
    case Kwilu = 'kwilu';
    case MaiNdombe = 'mai_ndombe';
    case Kasai = 'kasai';
    case KasaiCentral = 'kasai_central';
    case KasaiOriental = 'kasai_oriental';
    case Lomami = 'lomami';
    case Sankuru = 'sankuru';
    case Maniema = 'maniema';
    case SudKivu = 'sud_kivu';
    case NordKivu = 'nord_kivu';
    case Ituri = 'ituri';
    case HautUele = 'haut_uele';
    case Tshopo = 'tshopo';
    case BasUele = 'bas_uele';
    case Nord_Ubangi = 'nord_ubangi';
    case Mongala = 'mongala';
    case SudUbangi = 'sud_ubangi';
    case Equateur = 'equateur';
    case Tshuapa = 'tshuapa';
    case HautKatanga = 'haut_katanga';
    case HautLomami = 'haut_lomami';
    case Lualaba = 'lualaba';
    case Tanganyika = 'tanganyika';

    public function label(): string
    {
        return match ($this) {
            self::Kinshasa => 'Kinshasa',
            self::KongoCentral => 'Kongo-Central',
            self::Kwango => 'Kwango',
            self::Kwilu => 'Kwilu',
            self::MaiNdombe => 'Mai-Ndombe',
            self::Kasai => 'Kasaï',
            self::KasaiCentral => 'Kasaï-Central',
            self::KasaiOriental => 'Kasaï-Oriental',
            self::Lomami => 'Lomami',
            self::Sankuru => 'Sankuru',
            self::Maniema => 'Maniema',
            self::SudKivu => 'Sud-Kivu',
            self::NordKivu => 'Nord-Kivu',
            self::Ituri => 'Ituri',
            self::HautUele => 'Haut-Uélé',
            self::Tshopo => 'Tshopo',
            self::BasUele => 'Bas-Uélé',
            self::Nord_Ubangi => 'Nord-Ubangi',
            self::Mongala => 'Mongala',
            self::SudUbangi => 'Sud-Ubangi',
            self::Equateur => 'Équateur',
            self::Tshuapa => 'Tshuapa',
            self::HautKatanga => 'Haut-Katanga',
            self::HautLomami => 'Haut-Lomami',
            self::Lualaba => 'Lualaba',
            self::Tanganyika => 'Tanganyika',
        };
    }
}

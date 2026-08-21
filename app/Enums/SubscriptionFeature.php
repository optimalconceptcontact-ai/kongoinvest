<?php

namespace App\Enums;

/**
 * Boolean capability flags. Numeric caps (project count, AI quota) live
 * in a plan's `limits` JSON instead — see SubscriptionPlan::limit().
 * Both are admin-editable data, never hardcoded in React (spec §28).
 */
enum SubscriptionFeature: string
{
    case AdvancedMatching = 'advanced_matching';
    case AiCoach = 'ai_coach'; // entrepreneur "improve my project" (§53)
    case AiInvestmentAdvisor = 'ai_investment_advisor'; // investor advisor (§54)
    case ContactInfoVisible = 'contact_info_visible'; // §31, §108
    case NegotiationTools = 'negotiation_tools';
    case DealRoomAccess = 'deal_room_access';
    case EscrowAccess = 'escrow_access';
    case KycAccess = 'kyc_access';
    case ContractsAccess = 'contracts_access';
    case PortfolioTracking = 'portfolio_tracking'; // investor (§31)
}

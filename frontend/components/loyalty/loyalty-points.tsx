'use client';

import { useState } from 'react';
import { cn, formatCurrency } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { showToast } from '@/components/ui/toast-provider';

// Loyalty Points Display
interface LoyaltyPointsDisplayProps {
  points: number;
  tier?: 'bronze' | 'silver' | 'gold' | 'platinum';
  nextTierPoints?: number;
  pointsValue?: number; // Value of 1 point in currency
  className?: string;
}

export function LoyaltyPointsDisplay({
  points,
  tier = 'bronze',
  nextTierPoints = 1000,
  pointsValue = 100, // 1 point = Rp 100
  className,
}: LoyaltyPointsDisplayProps) {
  const tierConfig = {
    bronze: { 
      color: 'from-amber-600 to-amber-700', 
      icon: '🥉',
      benefits: 'Earn 1 point per Rp 10.000 spent'
    },
    silver: { 
      color: 'from-gray-400 to-gray-500', 
      icon: '🥈',
      benefits: 'Earn 1.5x points, Birthday reward'
    },
    gold: { 
      color: 'from-yellow-400 to-yellow-500', 
      icon: '🥇',
      benefits: 'Earn 2x points, Free delivery, Priority support'
    },
    platinum: { 
      color: 'from-purple-500 to-purple-600', 
      icon: '💎',
      benefits: 'Earn 3x points, Exclusive offers, VIP events'
    },
  };

  const config = tierConfig[tier];
  const progress = Math.min((points / nextTierPoints) * 100, 100);
  const valueInRupiah = points * pointsValue;

  return (
    <div className={cn('rounded-2xl overflow-hidden', className)}>
      <div className={cn('bg-gradient-to-r p-6 text-white', config.color)}>
        <div className="flex items-center justify-between mb-4">
          <div>
            <p className="text-white/80 text-sm">Your Points</p>
            <p className="text-3xl font-bold">{points.toLocaleString()}</p>
            <p className="text-sm text-white/80">
              ≈ {formatCurrency(valueInRupiah)}
            </p>
          </div>
          <div className="text-4xl">{config.icon}</div>
        </div>

        <div className="space-y-2">
          <div className="flex justify-between text-sm">
            <span className="capitalize">{tier} Member</span>
            <span>{points.toLocaleString()} / {nextTierPoints.toLocaleString()}</span>
          </div>
          <div className="h-2 bg-white/30 rounded-full overflow-hidden">
            <div
              className="h-full bg-white rounded-full transition-all duration-500"
              style={{ width: `${progress}%` }}
            />
          </div>
          <p className="text-xs text-white/70">
            {nextTierPoints - points > 0
              ? `${(nextTierPoints - points).toLocaleString()} points to next tier`
              : 'Maximum tier reached!'}
          </p>
        </div>
      </div>

      <div className="bg-white dark:bg-gray-800 p-4">
        <p className="text-sm text-gray-600 dark:text-gray-400">
          <span className="font-medium">Benefits:</span> {config.benefits}
        </p>
      </div>
    </div>
  );
}

// Loyalty Points Redemption
interface RedeemOption {
  id: string;
  name: string;
  description: string;
  pointsCost: number;
  value?: number; // In currency
  image?: string;
  type: 'discount' | 'product' | 'voucher' | 'freeitem';
}

interface LoyaltyRedemptionProps {
  availablePoints: number;
  options: RedeemOption[];
  onRedeem: (optionId: string) => Promise<void>;
  className?: string;
}

export function LoyaltyRedemption({
  availablePoints,
  options,
  onRedeem,
  className,
}: LoyaltyRedemptionProps) {
  const [redeeming, setRedeeming] = useState<string | null>(null);

  const handleRedeem = async (option: RedeemOption) => {
    if (availablePoints < option.pointsCost) {
      showToast.error('Not enough points');
      return;
    }

    setRedeeming(option.id);
    try {
      await onRedeem(option.id);
      showToast.success(`Successfully redeemed: ${option.name}`);
    } catch {
      showToast.error('Failed to redeem. Please try again.');
    } finally {
      setRedeeming(null);
    }
  };

  const typeIcons = {
    discount: '🏷️',
    product: '☕',
    voucher: '🎟️',
    freeitem: '🎁',
  };

  return (
    <div className={className}>
      <div className="mb-4 p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
        <p className="text-sm text-amber-800 dark:text-amber-200">
          Your Balance: <span className="font-bold">{availablePoints.toLocaleString()} points</span>
        </p>
      </div>

      <div className="grid gap-3">
        {options.map((option) => {
          const canRedeem = availablePoints >= option.pointsCost;
          
          return (
            <Card
              key={option.id}
              variant="outlined"
              padding="sm"
              className={cn(!canRedeem && 'opacity-50')}
            >
              <div className="flex items-center gap-4">
                <div className="text-3xl flex-shrink-0">
                  {typeIcons[option.type]}
                </div>
                
                <div className="flex-1 min-w-0">
                  <h4 className="font-medium text-gray-900 dark:text-gray-100">
                    {option.name}
                  </h4>
                  <p className="text-sm text-gray-500 dark:text-gray-400 truncate">
                    {option.description}
                  </p>
                  {option.value && (
                    <p className="text-xs text-amber-600 mt-0.5">
                      Worth {formatCurrency(option.value)}
                    </p>
                  )}
                </div>

                <div className="flex-shrink-0 text-right">
                  <p className="font-bold text-amber-600 mb-1">
                    {option.pointsCost.toLocaleString()} pts
                  </p>
                  <Button
                    size="sm"
                    variant={canRedeem ? 'primary' : 'secondary'}
                    disabled={!canRedeem || redeeming === option.id}
                    isLoading={redeeming === option.id}
                    onClick={() => handleRedeem(option)}
                  >
                    Redeem
                  </Button>
                </div>
              </div>
            </Card>
          );
        })}
      </div>
    </div>
  );
}

// Points History
interface PointTransaction {
  id: string;
  type: 'earned' | 'redeemed' | 'expired' | 'bonus';
  points: number;
  description: string;
  date: string;
  orderId?: string;
}

interface PointsHistoryProps {
  transactions: PointTransaction[];
  className?: string;
}

export function PointsHistory({ transactions, className }: PointsHistoryProps) {
  const typeConfig = {
    earned: { color: 'text-green-600', prefix: '+', icon: '↑' },
    redeemed: { color: 'text-red-600', prefix: '-', icon: '↓' },
    expired: { color: 'text-gray-400', prefix: '-', icon: '×' },
    bonus: { color: 'text-amber-600', prefix: '+', icon: '★' },
  };

  if (transactions.length === 0) {
    return (
      <div className="text-center py-8 text-gray-500 dark:text-gray-400">
        No points history yet
      </div>
    );
  }

  return (
    <div className={cn('divide-y divide-gray-200 dark:divide-gray-700', className)}>
      {transactions.map((tx) => {
        const config = typeConfig[tx.type];
        
        return (
          <div key={tx.id} className="py-3 flex items-center justify-between">
            <div className="flex items-center gap-3">
              <span className={cn('text-lg', config.color)}>{config.icon}</span>
              <div>
                <p className="text-sm font-medium text-gray-900 dark:text-gray-100">
                  {tx.description}
                </p>
                <p className="text-xs text-gray-500 dark:text-gray-400">
                  {new Date(tx.date).toLocaleDateString('id-ID', {
                    day: 'numeric',
                    month: 'short',
                    year: 'numeric',
                  })}
                </p>
              </div>
            </div>
            <span className={cn('font-semibold', config.color)}>
              {config.prefix}{tx.points.toLocaleString()}
            </span>
          </div>
        );
      })}
    </div>
  );
}

// Points Earn Calculator
interface PointsEarnCalculatorProps {
  orderTotal: number;
  pointsPerAmount?: number; // Points earned per currency unit (e.g., 1 point per Rp 10.000)
  multiplier?: number; // Tier multiplier
  className?: string;
}

export function PointsEarnCalculator({
  orderTotal,
  pointsPerAmount = 10000,
  multiplier = 1,
  className,
}: PointsEarnCalculatorProps) {
  const basePoints = Math.floor(orderTotal / pointsPerAmount);
  const totalPoints = Math.floor(basePoints * multiplier);

  return (
    <div className={cn(
      'bg-gradient-to-r from-amber-50 to-yellow-50 dark:from-amber-900/20 dark:to-yellow-900/20 rounded-lg p-4',
      className
    )}>
      <div className="flex items-center justify-between">
        <div>
          <p className="text-sm text-gray-600 dark:text-gray-400">You&apos;ll earn</p>
          <p className="text-2xl font-bold text-amber-600">
            +{totalPoints.toLocaleString()} points
          </p>
          {multiplier > 1 && (
            <p className="text-xs text-amber-500">
              Including {multiplier}x member bonus!
            </p>
          )}
        </div>
        <div className="text-4xl">🎁</div>
      </div>
    </div>
  );
}

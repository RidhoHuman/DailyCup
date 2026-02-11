import { cn } from '@/lib/utils';

interface BadgeProps {
  children: React.ReactNode;
  variant?: 'default' | 'primary' | 'secondary' | 'success' | 'warning' | 'danger' | 'info';
  size?: 'sm' | 'md' | 'lg';
  dot?: boolean;
  removable?: boolean;
  onRemove?: () => void;
  className?: string;
}

export function Badge({
  children,
  variant = 'default',
  size = 'md',
  dot = false,
  removable = false,
  onRemove,
  className,
}: BadgeProps) {
  const variants = {
    default: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
    primary: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
    secondary: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
    success: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
    warning: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
    danger: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
    info: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
  };

  const dotColors = {
    default: 'bg-gray-500',
    primary: 'bg-amber-500',
    secondary: 'bg-gray-400',
    success: 'bg-green-500',
    warning: 'bg-yellow-500',
    danger: 'bg-red-500',
    info: 'bg-blue-500',
  };

  const sizes = {
    sm: 'px-2 py-0.5 text-xs',
    md: 'px-2.5 py-1 text-sm',
    lg: 'px-3 py-1.5 text-base',
  };

  return (
    <span
      className={cn(
        'inline-flex items-center gap-1.5 font-medium rounded-full',
        variants[variant],
        sizes[size],
        className
      )}
    >
      {dot && (
        <span className={cn('w-1.5 h-1.5 rounded-full', dotColors[variant])} />
      )}
      {children}
      {removable && (
        <button
          onClick={onRemove}
          className="ml-0.5 hover:bg-black/10 dark:hover:bg-white/10 rounded-full p-0.5 transition-colors"
          aria-label="Remove"
        >
          <svg className="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      )}
    </span>
  );
}

// Notification Badge (for icons)
interface NotificationBadgeProps {
  count?: number;
  max?: number;
  showZero?: boolean;
  dot?: boolean;
  color?: 'primary' | 'danger' | 'success' | 'warning';
  children: React.ReactNode;
  className?: string;
}

export function NotificationBadge({
  count = 0,
  max = 99,
  showZero = false,
  dot = false,
  color = 'danger',
  children,
  className,
}: NotificationBadgeProps) {
  const shouldShow = dot || (showZero ? count >= 0 : count > 0);
  const displayCount = count > max ? `${max}+` : count;

  const colors = {
    primary: 'bg-amber-500',
    danger: 'bg-red-500',
    success: 'bg-green-500',
    warning: 'bg-yellow-500',
  };

  return (
    <span className={cn('relative inline-flex', className)}>
      {children}
      {shouldShow && (
        <span
          className={cn(
            'absolute flex items-center justify-center',
            colors[color],
            dot
              ? 'top-0 right-0 w-2 h-2 rounded-full'
              : 'top-0 right-0 transform translate-x-1/3 -translate-y-1/3 min-w-[18px] h-[18px] px-1 text-xs font-bold text-white rounded-full'
          )}
        >
          {!dot && displayCount}
        </span>
      )}
    </span>
  );
}

// Status Badge (for order status, etc.)
interface StatusBadgeProps {
  status: 'pending' | 'processing' | 'paid' | 'shipped' | 'delivered' | 'cancelled' | 'failed';
  size?: 'sm' | 'md';
  className?: string;
}

export function StatusBadge({ status, size = 'md', className }: StatusBadgeProps) {
  const statusConfig: Record<
    StatusBadgeProps['status'],
    { label: string; variant: BadgeProps['variant'] }
  > = {
    pending: { label: 'Pending', variant: 'warning' },
    processing: { label: 'Processing', variant: 'info' },
    paid: { label: 'Paid', variant: 'success' },
    shipped: { label: 'Shipped', variant: 'primary' },
    delivered: { label: 'Delivered', variant: 'success' },
    cancelled: { label: 'Cancelled', variant: 'secondary' },
    failed: { label: 'Failed', variant: 'danger' },
  };

  const config = statusConfig[status] || { label: status, variant: 'default' };

  return (
    <Badge variant={config.variant} size={size} dot className={className}>
      {config.label}
    </Badge>
  );
}

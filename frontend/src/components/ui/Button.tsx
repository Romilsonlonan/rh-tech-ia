import { ButtonProps } from '@/types';
import { cn } from '@/utils/cn';

export const Button = ({ 
  variant = 'primary', 
  children, 
  onClick, 
  className,
  type = 'button'
}: ButtonProps) => {
  return (
    <button
      type={type}
      onClick={onClick}
      className={cn(
        variant === 'primary' ? 'btn-primary' : 'btn-secondary',
        className
      )}
    >
      {children}
    </button>
  );
};

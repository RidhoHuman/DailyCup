import Image from "next/image";

interface FacebookProps {
  className?: string;
}

export const Facebook = ({ className }: FacebookProps) => {
  return (
    <Image
      src="/assets/image/facebook.png"
      alt="Facebook"
      width={48}
      height={48}
      className={className}
    />
  );
};
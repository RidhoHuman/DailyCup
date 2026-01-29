import Image from "next/image";

interface GoogleProps {
  className?: string;
}

export const Google = ({ className }: GoogleProps) => {
  return (
    <Image
      src="/assets/image/google.png"
      alt="Google"
      width={48}
      height={48}
      className={className}
    />
  );
};
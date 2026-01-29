interface StatsCardProps {
  title: string;
  value: string;
  icon: string;
  trend?: string;
  trendUp?: boolean;
  color?: string;
}

export default function StatsCard({ title, value, icon, trend, trendUp, color = "bg-blue-500" }: StatsCardProps) {
  return (
    <div className="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
      <div className="flex justify-between items-start">
        <div>
          <p className="text-sm font-medium text-gray-500">{title}</p>
          <h3 className="text-2xl font-bold text-gray-800 mt-2">{value}</h3>
          
          {trend && (
            <div className={`flex items-center mt-2 text-sm ${trendUp ? 'text-green-600' : 'text-red-600'}`}>
              <i className={`bi ${trendUp ? 'bi-arrow-up-short' : 'bi-arrow-down-short'} text-lg mr-1`}></i>
              <span className="font-medium">{trend}</span>
              <span className="text-gray-400 ml-1">vs last month</span>
            </div>
          )}
        </div>
        
        <div className={`w-12 h-12 rounded-xl flex items-center justify-center text-white text-xl shadow-lg ${color}`}>
          <i className={`bi ${icon}`}></i>
        </div>
      </div>
    </div>
  );
}

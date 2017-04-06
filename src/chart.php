<?php
	namespace Chart\chart;
	
	final class chartData {
		public $title;
		public $legends;
		public $ticks;
		public $data;
	}

	final class svgChart
	{
		private $type;
		private $svg;

		private $canvasw = 800;
		private $canvash = 400;

		private $chartw = 630;
		private $charth = 300;

		private $x0 = 60;
		private $y0 = 50;

		private $legendw = 80;
		private $legendihw = 12;
		private $legendx;
		private $legendiconx;
		private $legendgap = 15;

		private $seriesSpan;

		private $minValue;
		private $maxValue;
		private $valueSpan;

		//private $fillColors = ["#4bb2c5", "#579575", "#EAA228", "#839557", "#c5b47f", "#958c12"];
		private $fillColors = ["#f6d879", "#a6d391", "#ec7995", "#79aad1"];

		private $colors = ["text"=>"#333333", "frame"=>"#aaaaaa", "grid"=>"#cccccc"];

		public function __construct($type, &$data)
		{
			$this->type = $type;

			$this->legendx = $this->x0 + $this->chartw + 15;
			$this->legendiconx = $this->legendx + 10;

			$dt = $data->data;
			$this->minValue = $this->maxValue = $dt[0][0];
			for ($i = 0; $i < count($dt); ++$i)
				for ($j = 0; $j < count($dt[0]); ++$j)
				{
					if ($this->minValue > $dt[$i][$j]) $this->minValue = $dt[$i][$j];
					if ($this->maxValue < $dt[$i][$j]) $this->maxValue = $dt[$i][$j];
				}

			$this->minValue = (intval($this->minValue / 10.0)) * 10;
			$this->maxValue = (intval($this->maxValue / 10.0) + 1) * 10;

			$this->svgHeader($data);
			$this->chartCoordinate($data->data);
			$this->chartLegend($data->legends);
			switch ($type)
			{
				case "bar": 
					$this->chartBar($data->data);
					break;

				case "line":
					$this->chartLine($data->data);
					break;
			}
			$this->chartTicks($data);
			$this->svgFooter();
		}

		public function getChart()
		{
			return $this->svg;
		}

		public function valueToHeight($value)
		{
			return ($value - $this->minValue) / $this->valueSpan * $this->charth;
		}

		private function svgHeader(&$data)
		{
			//css
			$this->svg = "<style> svg .series { fill-opacity: 0.6; } ";
			for ($i = 1; $i <= count($data->data); ++$i)
			{	
				$fc = $this->fillColors[($i-1) % count($this->fillColors)];
				$this->svg .= "svg .series{$i} { fill: {$fc}; stroke: {$fc}; } ";
			}
			$this->svg .= "svg .text {font-family:'Calibri', '等线', 'Microsoft Yahei';fill:{$this->colors['text']};fill-opacity:1;text-anchor: middle;stroke:none;} ";
			$this->svg .= "svg .legend {font-size: .9rem;text-anchor: start;} ";
			$this->svg .= "svg .title {font-size: 1.5rem;} ";
			$this->svg .= "svg .label {font-size: .8rem;} ";
			$this->svg .= "svg .tick {font-size: 1rem;} ";
			$this->svg .= "svg .axis {font-size: 1rem;} ";
			$this->svg .= "</style>";

			$tx = $this->canvasw / 2;
			$ty = 30;
			$this->svg .= <<<EOT
<svg  class="grayable"
	viewBox="0 0 800 400"
	
	version="1.1">

	<defs>
EOT;
			
			for ($i = 0; $i < count($this->fillColors); ++$i)
				$this->svg .= <<<EOT
				  <marker id="marker{$i}" markerWidth="8" markerHeight="8" refx="5" refy="5">
				    <circle cx="5" cy="5" r="2" style="stroke: none; fill:{$this->fillColors[$i]};"/>
				  </marker>
EOT;

			$this->svg .= <<<EOT
	</defs>
	<g transform="translate(0,0)">
		<text class="text title">
	  		<tspan x="{$tx}" y="{$ty}">{$data->title}</tspan>
	  	</text>
  	</g>
EOT;
		}

		private function chartCoordinate(&$data)
		{
			$this->svg .= <<<EOT
	<g
	    transform="translate(0,0)"
	    style="fill-opacity:0;stroke-opacity:1;stroke:{$this->colors['grid']};stroke-width:1;">
EOT;

			//horizental grid line
			$maxn = 11;
			$vstep = 5;
			$n = 0;
			while (true)
			{
				$n = intval(($this->maxValue - $this->minValue) / $vstep + 0.5);
				if ($n <= $maxn) break;
				$vstep *= 2;
				if ($vstep > 20) $vstep = 50;
			}

			$x1 = $this->x0 - 10;
			$xaxis = $x1 - 20;
			$x2 = $this->x0 + $this->chartw;
			$y1 = $this->y0;
			$vspan = $this->charth * 1.0 / $n;
			$this->maxValue = $taxis = $this->minValue + $n * $vstep;
			$this->valueSpan = $this->maxValue - $this->minValue;
			for ($i = 0; $i <= $n; ++$i, $y1 += $vspan, $taxis -= $vstep)
			{
				$s = sprintf("%d", $taxis);
				$ty = $y1 + 4;
     			$this->svg .= <<<EOT
    				<line x1="{$x1}" y1="{$y1}" x2="{$x2}" y2="{$y1}" />
    				<text class="text axis">
						<tspan x="{$xaxis}" y="{$ty}">{$s}</tspan>
					</text>
EOT;
			}

			//vertical grid line
			$x1 = $this->x0;
			$y1 = $this->y0;
			$y2 = $y1 + $this->charth + 10;
			$dc = count($data[0]);
			$this->seriesSpan = $this->chartw / $dc;
			for ($i = 0; $i <= $dc; ++$i, $x1 += $this->seriesSpan)
				$this->svg .= <<<EOT
					<line x1="{$x1}" y1="{$y1}" x2="{$x1}" y2="{$y2}" />
EOT;

       		$this->svg .= <<<EOT
       	<rect style="stroke-width:2;stroke:{$this->colors['frame']}" id="frame" width="{$this->chartw}" height="{$this->charth}" x="{$this->x0}" y="{$this->y0}" />
    </g>
EOT;
		}

		private function chartLegend(&$legends)
		{
			$this->svg .= <<<EOT
	<g
     transform="translate(0,0)"
     style="fill-opacity:0;stroke-opacity:1;stroke:{$this->colors['frame']};stroke-width:1;">
EOT;

			$lgc = count($legends);
			$lh = $lgc * ($this->legendihw + $this->legendgap) + $this->legendgap;
			$this->svg .= <<<EOT
      	<rect width="{$this->legendw}" height="{$lh}" x="{$this->legendx}" y="{$this->y0}" />
EOT;

			$lx = $this->legendiconx + $this->legendihw + 10;
			for ($ly = $this->y0 + $this->legendgap, $i = 1; $i <= $lgc; ++$i)
			{				
				$j = $i - 1;
				$lly = $ly + 10;
				$this->svg .= <<<EOT
				<rect class="series series{$i}" width="{$this->legendihw}" height="$this->legendihw" x="{$this->legendiconx}" y="{$ly}" />
				<text class="text legend">
					<tspan x="{$lx}" y="{$lly}">{$legends[$j]}</tspan>
				</text>
EOT;
				$ly += $this->legendgap + $this->legendihw;
			}

			$this->svg .= "</g>";

		}

		private function chartBar(&$data)
		{
			$this->svg .= <<<EOT
	<g
    class="series"
    transform="translate(0,400)"
    style="stroke-width:1;">
EOT;
			
			$dc = count($data);
			$sc = count($data[0]);

			$barGap = 10;
			$barw = ($this->seriesSpan - ($dc + 1) * $barGap) / $dc;
			for ($i = 0; $i < $sc; ++$i)
			{
				$barx = $this->x0 + $barGap + $i * $this->seriesSpan;
				for ($j = 1; $j <= $dc; ++$j, $barx += $barGap + $barw)
				{
					$k = $j - 1;
					$barh = $this->valueToHeight($data[$k][$i]);
					$y = -($barh + $this->y0);
					$lby = $y - 5;
					$lbx = $barx + $barw / 2.0;
					$s = sprintf("%.1f", $data[$k][$i]);
					$this->svg .= <<<EOT
		<rect class="series{$j}" width="{$barw}" height="{$barh}" x="{$barx}" y="{$y}" />
		<text class="text label">
			<tspan x="{$lbx}" y="{$lby}">{$s}</tspan>
		</text>
EOT;
				}
			}
  			$this->svg .= "</g>";
		}

		private function chartLine(&$data)
		{
			$this->svg .= <<<EOT
	<g
    class=""
    transform="translate(0,400)"
    style="fill-opacity:0;stroke-opacity:1;stroke-width:2;">
EOT;
			
			$dc = count($data);
			$sc = count($data[0]);

			$path = [];
			$lbs = "";
			for ($i = 0; $i < $dc; ++$i)
			{
				$path[$i] = "";
				$x = $this->x0 + $this->seriesSpan / 2.0;
				for ($j = 0; $j < $sc; ++$j)
				{
					$h = $this->valueToHeight($data[$i][$j]);
					$y = -($h + $this->y0);
					$lbx = $x;
					$lby = $y;
					$s = sprintf("%d", $data[$i][$j]);
					$lbs .= <<<EOT
						<tspan x="{$lbx}" y="{$lby}">{$s}</tspan>
EOT;
					$path[$i] .= "{$x},{$y} ";
					$x += $this->seriesSpan;
				}

				$j = $i % count($this->fillColors);
				$this->svg .= <<<EOT
		<polyline points="{$path[$i]}" style="stroke:{$this->fillColors[$j]};marker-start: url(#marker{$j});marker-mid:url(#marker{$j});marker-end: url(#marker{$j})" />
EOT;
			}

			$this->svg .= "<text class='text label'>{$lbs}</text></g>";
		}

		private function chartTicks(&$data)
		{
			$this->svg .= "<g transform='translate(0,0)'><text class='text tick'>";

			$tkx = $this->x0 + $this->seriesSpan / 2.0;
			$tky = $this->y0 + $this->charth + 30;
			foreach ($data->ticks as $tick)
			{
				$this->svg .= "<tspan x='{$tkx}' y='{$tky}'>{$tick}</tspan>";
				$tkx += $this->seriesSpan;
			}

    		$this->svg .= "</text></g>";
		}

		private function svgFooter()
		{
			$this->svg .= "</svg>";
		}
	}

?>
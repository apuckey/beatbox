<?hh

namespace beatbox;

trait Compare implements Comparable {
	public function eq($other) : \bool { return $this->cmp($other) == 0; }
	public function ne($other) : \bool { return $this->cmp($other) != 0; }

	public function lt($other) : \bool { return $this->cmp($other) <  0; }
	public function le($other) : \bool { return $this->cmp($other) <= 0; }

	public function gt($other) : \bool { return $this->cmp($other) >  0; }
	public function ge($other) : \bool { return $this->cmp($other) >= 0; }
}
